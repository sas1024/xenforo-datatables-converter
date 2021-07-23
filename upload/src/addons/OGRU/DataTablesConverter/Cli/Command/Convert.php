<?php

    namespace OGRU\DataTablesConverter\Cli\Command;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use XF\Entity\Post;
    use XF\PrintableException;

    class Convert extends Command {

        private $separators;

        protected static $_separators = array(
            'comma' => ',',
            'pipe'  => '\|',
            'tab'   => '\t'
        );


        /**
         * @return void
         */
        protected function configure() {
            $this
                ->setName( 'ogru:datatables:convert' )
                ->setDescription( 'Convert DataTables BBCodes to native XF 2.x tables' );
        }


        /**
         * @param InputInterface  $input
         * @param OutputInterface $output
         * @return int|null
         * @throws PrintableException
         */
        protected function execute( InputInterface $input, OutputInterface $output ) {
            $options = \XF::options();

            $selectedSeparators = array_filter( $options->ogruDataTablesSeparator );

            if ( empty( $selectedSeparators ) ) {
                $output->writeln( 'Error! Need to select column separators in admin options' );
                return 1;
            }

            $this->separators = array_values( array_intersect_key( self::$_separators,
                array_filter( $options->ogruDataTablesSeparator ) ) );

            $output->writeln( 'Start converting...' );

            $db    = \XF::db();
            $posts = $db->fetchAll( "SELECT * FROM xf_post WHERE message LIKE '%[table%' AND message NOT LIKE '%[tr]%' AND message_state='visible'" );

            $output->writeln( sprintf( 'Found %d messages with DataTables BB Code', count( $posts ) ) );
            $skipped = 0;

            $updatedMessages = [];
            foreach ( $posts as $post ) {
                $postId = $post['post_id'];
                $output->write( sprintf( 'Converting post_id %d...', $postId ) );
                $message = preg_replace_callback(
                    '/\[table?.*\](.*)\[\/table]/imusU',
                    [ $this, 'convertDataTable' ],
                    $post['message']
                );
                if ( $post['message'] === $message ) {
                    $output->writeln( 'Error! Invalid table bbcode data detected!' );
                    $skipped++;
                    continue;
                }
                $output->writeln( 'Done' );

                $updatedMessages[$postId] = $message;
            }

            $db->beginTransaction();
            foreach ( $updatedMessages as $postId => $message ) {
                $db->update( 'xf_post', [ 'message' => $message ], 'post_id = ' . $postId );
                $output->writeln( sprintf( 'Saving post_id %d...', $postId ) );
            }
            $db->commit();


            $output->writeln( sprintf( 'Done! Successfully converted tables in %d messages, skipped %d messages', count( $posts ) - $skipped, $skipped ) );

            return 0;
        }


        /***
         * convert DataTable BBCode to native XF 2.x tables
         * @param $input
         * @return string
         */
        private function convertDataTable( $input ) {
            $pattern = implode( '|', $this->separators );

            $rowStrings = preg_split( '/\n|\r\n?/', trim( $input[1] ) );
            $rows       = [];
            foreach ( $rowStrings as $i => $row ) {
                if ( $pattern ) {
                    $rows[$i] = preg_split( '/' . $pattern . '/', $row );
                } else {
                    $rows[$i] = $row;
                }
            }

            $haveHeader = strpos( strtolower( $input[0] ), '[table=head]' );
            if ( $haveHeader !== false ) {
                $headerRow     = $rows[0];
                $headerColumns = [];
                foreach ( $headerRow as $column ) {
                    $headerColumns[] = sprintf( '[B]%s[/B]', $column );
                }
                $rows[0] = $headerColumns;
            }

            $out = "[TABLE]";
            foreach ( $rows as $row ) {
                $out .= '[TR]';
                foreach ( $row as $column ) {
                    $out .= '[TD]' . $column . '[/TD]';
                }
                $out .= "[/TR]\r\n";
            }

            $out .= "[/TABLE]";

            return $out;
        }
    }

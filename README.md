# xenforo-datatables-converter
This XenForo addon can convert old [TH] DataTables BB Code to native XenForo 2.x tables.

It can be useful for migration from XenForo 1.5 to XenForo 2.x

I tried to convert over DataTables BB Code from 1300 messages, and all messages was converted successfully.

## Requirements
- XenForo 2.2.x
- PHP 7.x
- Access to command line interface on server

## How to use it:

- You need to setup DataTables separator in XenForo admin panel.
- From command line run `php cmd.php ogru:datatables:convert`
- You will get report about converted messages, and skipped messages with errors.

## Notes
- It safe to run command several times

# Changelog

### 0.6.5 (2022-10.31)

- fix for PHP8 -> "mixed" as native type always wins

### 0.6.4 (2022-08-29)

- fix "checkPhpDocType" checks for php docs vs. php types v2

### 0.6.3 (2022-08-29)

- fix "checkPhpDocType" checks for php docs vs. php types

### 0.6.2 (2022-06-22)

- ignore missing phpdoc from \Exception classes

### 0.6.1 (2022-02-15)

- support "<phpdoctor-ignore-this-line/>" for properties | thanks @pmagentur
- update "voku/Simple-PHP-Code-Parser"

### 0.6.0 (2022-02-02)

- clean-up / update dependencies
- fix for phar file and "child-process" issue v2

### 0.5.2 (2021-12-09)

- update dependencies
- fix for phar file and "child-process" issue

### 0.5.1 (2021-10-03)

- update dependencies

### 0.5.0 (2021-08-21)

- add support for traits

### 0.4.1 (2021-07-27)

- update dependencies

### 0.4.0 (2020-10-31)

- allow to ignore reported errors via ```<phpdoctor-ignore-this-line/>```
- sort the error output by line number

### 0.3.0 (2020-10-30)

- PHP 7.2 as minimal requirement
- support for modern phpdocs for "@param" and "@return"
- update vendor libs

### 0.2.0 (2020-08-04)

- allow "class-string" as string
- optimize performance
- breaking change in the CLI parameter: allow to analyse more then one directory / file at once

### 0.1.0 (2020-08-31)

- init (extracted from "voku/Simple-PHP-Code-Parser")

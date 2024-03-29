# swaml

This is a quicky tool I wrote to generate REST api documentation in the
[Swagger](http://developers.helloreverb.com/swagger/) format.
The idea is you take a directory hierarchy that matches your API, add .yaml
files for various HTTP methods (GET, POST, etc.) and swaml.php outputs a
directory of .json files you can then pass into, for example,
[Swagger UI](https://github.com/wordnik/swagger-ui).

## Usage

    php swaml.phar --api-base "http://your.api.base.path/" --doc-base "http://your.documentation/base/path" input_dir output_dir

## Building

A makefile is included to build `swaml.phar`. Run `make` from the project root.

## Why Does This Exist?

Hand-writing JSON files for Swagger documentation is kind of a pain. I wanted
a way to write docs that wasn't tightly coupled to the exact implementation
method of the API (like, not framework-specific), but that also allowed some
of the things those methods give you (DRY, etc.)

## TODO

- Full swagger spec support.
- Write some actual documentation.
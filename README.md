Resource Library Local plugin
=============================

[![Build Status](https://travis-ci.org/call-learning/moodle-local_edximport.svg?branch=master)](https://travis-ci.org/call-learning/moodle-local_edximport)

This plugin allows to import directly an edX course archive into Moodle. This is still
work in progress and for now only the basic use case is covered.

The process is as following:

1. The archive is uploaded in a temporary folder and uncompressed
2. An intermediate edX model is built in memory
3. We then generate a Moodle XML backup fileset that we import in Moodle

We might use this development to add a new backup converter (see the moodle backup: backup/converter). 

Installation
============

Just add the source code into the local/edximport folder

Usage
=====


Architecture
============

The converted is split into several modules:

1. edx (classes/edx): the intermediate in-memory model matching the edX archive.
2. moodle backup archive builder (classes/converter): this will build another intermediate
model / data structure that will then generate Moodle XML backup via mustache templates. 


Currently we convert a serie of static (html or video unit) into a book so we have a reasonable
amount of activities in a course. 


TODO
====

* We currently convert a series of vertical into a Moodle book to mimic the structure
from edX. We might just use a specific course format and use module indentation so we don't have
  to convert a series of "static html" into a book.



 
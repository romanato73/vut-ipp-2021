<?php

// Set allowed arguments for application.
return [
    'parse' => [
        "--errors",
    ],
    'test' => [
        "--errors",
        "--directory=path",
        "--recursive",
        "--parse-script=file",
        "--int-script=file",
        "--parse-only",
        "--int-only",
        "--jexamxml=file",
        "--jexamcfg=file",
    ],
];
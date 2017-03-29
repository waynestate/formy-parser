Content Parser
================

Parses a string for formy elements and converts the string to an HTML form Edit
Add topics

Installation
-----

    composer require waynestate/content-parser

Usage
-----

    <?php
        use Waynestate\FormyParser\Parser;
        use Waynestate\ParserMiddleware\ParserMiddleware;

        // Composer autoload
        require __DIR__ . '/../vendor/autoload.php';

        // Create the instance of the Parser Middleware
        $parser = new ParserMiddleware;

        // Set the stack of parsers to run
        $parser->setStack([
            'Waynestate\FormyParser\Parser'
        ]);

        // Original String we want to parse
        $string = '<p>Content before embed.</p><p>[form id="undergrad"]</p><p>Content after embed.</p>';

        // Generate the html form
        $html_form = $parser->parse($string);

        // Display the form with content surrounding it
        echo $html_form;

Tests
-----

    phpunit

Code Coverage
-----

    phpunit --coverage-html ./coverage

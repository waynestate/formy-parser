<?php

use Waynestate\FormyParser\Parser;

/**
 * Class ParserTest
 */
class ParserTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var FormyParser\Parser
     */
    protected $parser;

    /**
     * Setup
     */
    public function setUp()
    {
        // Set these super globals since the package relies on them
        $_SERVER['HTTP_USER_AGENT'] = '';
        $_SERVER['HTTP_HOST'] = '';
        $_SERVER['REQUEST_URI'] = '';
        $_SERVER['REMOTE_ADDR'] = '';

        // Initialize the parser
        $this->parser = new Waynestate\FormyParser\Parser;

        // Define the permalink to test against
        $this->permalink_undergradute = 'undergrad';
        $this->permalink_graduate = 'graduate';
    }

    /**
     * @test
     */
    public function parsing_no_embed_should_result_in_no_change()
    {
        $result = $this->parser->parse('foo');

        $this->assertEquals('foo', $result);
    }

    /**
     * @test
     */
    public function parsing_embed_should_have_html_form()
    {
        $result = $this->parser->parse('[form id="' . $this->permalink_undergradute . '"]');

        $this->assertContains('<form', $result);
    }

    /**
     * @test
     */
    public function parsing_embed_with_paragraph_tags_should_strip_paragraph_tags()
    {
        $result = $this->parser->parse('<p>[form id="' . $this->permalink_undergradute . '"]</p>');

        $this->assertTrue(substr($result, 0, 3) != '<p>', $result);
    }

    /**
     * @test
     */
    public function findincludes_should_parse_multiple_forms()
    {
        $result = $this->parser->findIncludes('[No Form]<p>[form id="' . $this->permalink_undergradute . '"]</p><p>[form id="'.$this->permalink_graduate.'"]</p>');

        $this->assertEquals($result[0]['id'], $this->permalink_undergradute);
        $this->assertEquals($result[1]['id'], $this->permalink_graduate);
    }
}

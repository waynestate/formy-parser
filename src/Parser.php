<?php namespace Waynestate\FormyParser;

use Waynestate\StringParser\StringParserInterface;

/**
 * Class ContentMiddleware
 * @package ContentMiddleware
 */
class Parser implements StringParserInterface {

    /**
     * @param string $string
     * @return string
     */
    public function parse($string)
    {
        // Find all the includes in this string
        $includes = $this->findIncludes($string);

        // For every form replace the embed
        foreach((array)$includes as $include)
        {
            // Start building the post
            $build_post = array('formy_permalink' => $include['id'], 'formy_database' => ((!isset($include['database']) || $include['database'] == '')?'prod':$include['database']), 'formy_button' => (!isset($include['button']) || $include['button']) ? '' : $include['button'], 'formy_responsive' => (!isset($include['responsive']) || $include['responsive'] == '') ? 'foundation4' : $include['responsive'], 'formy_form_action' => $_SERVER['REQUEST_URI'], 'formy_http_referrer' => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 'submission_ip' => $_SERVER['REMOTE_ADDR']);

            // Serialize post since CURL does not handle multi dimensional arrays well
            if(is_array($_POST))
            {
                $build_post['serialized_data'] = serialize($_POST);
            }

            // Add in the $_FILES
            if(is_array($_FILES) && count($_FILES) > 0)
            {
                foreach ($_FILES as $field_id => $file)
                {
                    // Only add if the file upload was OK
                    if($file['error'] == 0) {
                        $build_post[$field_id] = new \CURLFile($file['tmp_name'], $file['type'], basename($file['name']));
                    }
                }
            }

            // Version of the output
            $version = (defined('FORMY_OUTPUT_VERSION') == true) ? FORMY_OUTPUT_VERSION . '/' : '';

            // Curl the permalink/embed URL to get the raw html
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://forms.wayne.edu/' . $include['id'] . '/html/' . $version);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $build_post);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $session_name = ini_get('session.name');
            if(isset($_COOKIE[$session_name]) && $_COOKIE[$session_name] != '') {
                curl_setopt($ch, CURLOPT_COOKIE, $session_name.'='.$_COOKIE[$session_name]);
                session_write_close();
            }
            $info['form'] = curl_exec($ch);
            curl_close($ch);

            // Replace it in the contents or if its in a paragraph
            $string = trim(str_replace(["<p>" . $include['include'] . "</p>", $include['include']], $info['form'], stripslashes($string)));
        }

        return $string;
    }

    /**
     * @param string $string
     * @return array
     */
    private function findIncludes($string)
    {
        // This holds all the includes found
        $all_includes = [];

        // Find all the includes within the page content
        preg_match_all("/\[(.*)\]/", $string, $find_includes);

        // Loop through the includes (From above) and replace them
        if(is_array($find_includes[1]) && count($find_includes[1]) > 0) {
            foreach ($find_includes[1] as $key => $find) {
                $attribs = [];

                // Strip out the slashes
                $find = stripslashes($find);

                // Find the first space to split it up
                $first_space = strpos($find, ' ');

                // Set the type of include this is
                $attribs['type'] = substr($find, 0, $first_space);

                // Get just the attribute string
                $values = str_replace("&quot;", '"', trim(substr($find, $first_space)));

                // Get an array of attributes
                preg_match_all('#([^\s=]+)\s*=\s*(\'[^<\']*\'|"[^<"]*")#', $values, $attributes, PREG_SET_ORDER);

                // Clean up the attributes so they are easier to access
                if (is_array($attributes)) {
                    foreach ($attributes as $attrib) {
                        $attribs[$attrib[1]] = str_replace('"', '', $attrib[2]);
                    }
                }

                // Inject the original include so we can replace it
                $attribs['include'] = '[' . $find . ']';

                // Only add it in if the type is "form"
                if($attribs['type'] == 'form')
                    $all_includes[] = $attribs;
            }
        }

        return $all_includes;
    }
}

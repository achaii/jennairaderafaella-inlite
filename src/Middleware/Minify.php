<?php

namespace Jennairaderafaella\Inlite\Middleware;

use Closure;

class Minify
{
    /**
     * Handle an incoming request and minify HTML responses.
     *
     * @param mixed $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $contentType = $response->headers->get('Content-Type');

        // Check if the response content type is HTML
        if (strpos($contentType, 'text/html') !== false) {
            $buffer = $response->getContent();
            $replace = [
                '/<!--[^\[](.*?)[^\]]-->/s' => '', // Remove HTML comments
                "/<\?php/" => '<?php ', // Standardize PHP opening tags
                "/\r|\n|\t/" => '', // Remove newlines, carriage returns, and tabs
                "/>\s+</" => '><', // Minify spaces between HTML tags
            ];

            // Additional optimizations for non-<pre> HTML content
            if (strpos($buffer, '<pre>') === false) {
                $replace["/\n([\S])/"] = '$1'; // Remove newline characters followed by non-whitespace
                $replace["/ +/"] = ' '; // Reduce multiple spaces to a single space
            }

            // Perform the regex replacements on the HTML content
            $buffer = preg_replace(array_keys($replace), array_values($replace), $buffer);
            $response->setContent($buffer);

            // Enable gzip compression
            ini_set('zlib.output_compression', 'On');
        }

        return $response;
    }

    /**
     * Minify the given HTML data.
     *
     * @param string $data
     * @return string
     */
    public function minify($data)
    {
        $search = ['/>\s+/s', '/\s+</s'];
        $replace = ['> ', ' <'];
        return preg_replace($search, $replace, $data);
    }
}

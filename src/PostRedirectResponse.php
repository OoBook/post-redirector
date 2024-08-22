<?php

namespace OoBook\PostRedirector;

class PostRedirectResponse extends \Symfony\Component\HttpFoundation\Response
{
    /**
     * The data to be included in the POST request.
     *
     * @var object
     */
    protected $data;

    /**
     * Creates a redirect response so that it conforms to the rules defined for a redirect status code.
     *
     * @param string $url     The URL to redirect to. The URL should be a full URL, with schema etc.,
     *                        but practically every browser redirects on paths only as well
     * @param int    $status  The HTTP status code (307 "Found" by default)
     * @param array  $headers The headers (Location is always set to the given URL)
     *
     * @throws \InvalidArgumentException
     *
     * @see https://tools.ietf.org/html/rfc2616#section-10.3
     */
    public function __construct(string $url, int $status = 307, array $headers = [])
    {
        parent::__construct('', $status, $headers);

        if ('' === $url) {
            throw new \InvalidArgumentException('Cannot redirect to an empty URL.');
        }

        $this->targetUrl = $url;

        if (!$this->isRedirect()) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $status));
        }

        if (301 == $status && !\array_key_exists('cache-control', array_change_key_case($headers, \CASE_LOWER))) {
            $this->headers->remove('cache-control');
        }
    }

    /**
     * Sets the redirect target and data for this response.
     *
     * @param string $url
     * @param object|array $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTargetUrlAndData(string $url, $data)
    {
        $this->data = $data;

        // Set the HTML content for POST requests
        $this->setContent($this->getPostRedirectHtml($url, $data));

        return $this;
    }

    protected function buildFormFields($data, $prefix = null): string
    {
        $formFields = sprintf('<input type="hidden" name="%s" value="%s">', htmlspecialchars('_token', ENT_QUOTES, 'UTF-8'), htmlspecialchars((string) csrf_token(), ENT_QUOTES, 'UTF-8'));

        if (is_object($data)) {
            $data = (array) $data;
        }

        foreach ($data as $key => $value) {
            $name = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value) || is_object($value)) {
                $formFields .= $this->buildFormFields($value ,$name);
            } else {
                $formFields .= sprintf('<input type="hidden" name="%s" value="%s">', htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
            }
        }

        return $formFields;
    }

    /**
     * Get the HTML content for a POST redirect.
     *
     * @param string $url
     * @param object|array $data
     * @return string
     */
    protected function getPostRedirectHtml(string $url, $data): string
    {
        $formFields = $this->buildFormFields($data);
        return <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>Redirecting to $url</title>
    </head>
    <body onload="document.getElementById('redirect-form').submit();">
        <form id="redirect-form" action="$url" method="post">
            $formFields
        </form>
    </body>
</html>
HTML;
    }
}

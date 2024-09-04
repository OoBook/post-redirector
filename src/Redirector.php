<?php

namespace Oobook\PostRedirector;

class Redirector extends \Illuminate\Routing\Redirector
{
    /**
     * Create a new redirect response to the given path with data.
     *
     * @param string $path
     * @param object|array $data
     * @param int $status
     * @param array $headers
     * @param bool|null $secure
     * @return \Oobook\PostRedirector\PostRedirectResponse
     */
    public function toWithPayload($path, $data, $status = 307, $headers = [], $secure = null)
    {
        $url = $this->generator->to($path, [], $secure);

        return (new PostRedirectResponse($path, $status, $headers, $secure))->setTargetUrlAndData($url, $data);
    }
}

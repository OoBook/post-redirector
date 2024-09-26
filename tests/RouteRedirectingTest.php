<?php

namespace Oobook\PostRedirector\Tests;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Session\Store;
use Mockery as m;
use Oobook\PostRedirector\PostRedirectResponse;
use Oobook\PostRedirector\Redirector as PostRedirector;
use Symfony\Component\HttpFoundation\HeaderBag;

class RouteRedirectingTest extends TestCase
{
    protected $headers;
    protected $request;
    protected $url;
    protected $session;
    protected $redirect;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers = m::mock(HeaderBag::class);

        $this->request = m::mock(Request::class);
        $this->request->shouldReceive('isMethod')->andReturn(true)->byDefault();
        $this->request->shouldReceive('method')->andReturn('GET')->byDefault();
        $this->request->shouldReceive('route')->andReturn(true)->byDefault();
        $this->request->shouldReceive('ajax')->andReturn(false)->byDefault();
        $this->request->shouldReceive('expectsJson')->andReturn(false)->byDefault();
        $this->request->headers = $this->headers;

        $this->url = m::mock(UrlGenerator::class);
        $this->url->shouldReceive('getRequest')->andReturn($this->request);
        $this->url->shouldReceive('to')->with('bar', [], null)->andReturn('http://foo.com/bar');
        $this->url->shouldReceive('to')->with('bar', [], true)->andReturn('https://foo.com/bar');
        $this->url->shouldReceive('to')->with('login', [], null)->andReturn('http://foo.com/login');
        $this->url->shouldReceive('to')->with('http://foo.com/bar', [], null)->andReturn('http://foo.com/bar');
        $this->url->shouldReceive('to')->with('/', [], null)->andReturn('http://foo.com/');
        $this->url->shouldReceive('to')->with('http://foo.com/bar?signature=secret', [], null)->andReturn('http://foo.com/bar?signature=secret');

        $this->session = m::mock(Store::class);

        $this->redirect = new PostRedirector($this->url);
        $this->redirect->setSession($this->session);
    }

    protected function tearDown(): void
    {
        m::close();

        restore_error_handler();
        restore_exception_handler();
    }

    public function testBasicRedirectTo()
    {
        $response = $this->redirect->to('bar');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($this->session, $response->getSession());
    }

    public function testPostRedirectToWithPayload()
    {
        $response = $this->redirect->toWithPayload('bar', ['name' => 'test']);

        $this->assertInstanceOf(PostRedirectResponse::class, $response);
        $this->assertEquals(307, $response->getStatusCode());
        $this->assertEquals($response->getPayloadData(), ['name' => 'test']);
    }

    public function _testComplexRedirectTo()
    {
        $response = $this->redirect->to('bar', 303, ['X-RateLimit-Limit' => 60, 'X-RateLimit-Remaining' => 59], true);

        $this->assertSame('https://foo.com/bar', $response->getTargetUrl());
        $this->assertEquals(303, $response->getStatusCode());
        $this->assertEquals(60, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(59, $response->headers->get('X-RateLimit-Remaining'));
    }

    public function _testGuestPutCurrentUrlInSession()
    {
        $this->url->shouldReceive('full')->andReturn('http://foo.com/bar');
        $this->session->shouldReceive('put')->once()->with('url.intended', 'http://foo.com/bar');

        $response = $this->redirect->guest('login');

        $this->assertSame('http://foo.com/login', $response->getTargetUrl());
    }

    public function _testGuestPutPreviousUrlInSession()
    {
        $this->request->shouldReceive('isMethod')->once()->with('GET')->andReturn(false);
        $this->session->shouldReceive('put')->once()->with('url.intended', 'http://foo.com/bar');
        $this->url->shouldReceive('previous')->once()->andReturn('http://foo.com/bar');

        $response = $this->redirect->guest('login');

        $this->assertSame('http://foo.com/login', $response->getTargetUrl());
    }

    public function _testIntendedRedirectToIntendedUrlInSession()
    {
        $this->session->shouldReceive('pull')->with('url.intended', '/')->andReturn('http://foo.com/bar');

        $response = $this->redirect->intended();

        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function _testIntendedWithoutIntendedUrlInSession()
    {
        $this->session->shouldReceive('forget')->with('url.intended');

        // without fallback url
        $this->session->shouldReceive('pull')->with('url.intended', '/')->andReturn('/');
        $response = $this->redirect->intended();
        $this->assertSame('http://foo.com/', $response->getTargetUrl());

        // with a fallback url
        $this->session->shouldReceive('pull')->with('url.intended', 'bar')->andReturn('bar');
        $response = $this->redirect->intended('bar');
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function _testRefreshRedirectToCurrentUrl()
    {
        $this->request->shouldReceive('path')->andReturn('http://foo.com/bar');
        $response = $this->redirect->refresh();
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function _testBackRedirectToHttpReferer()
    {
        $this->headers->shouldReceive('has')->with('referer')->andReturn(true);
        $this->url->shouldReceive('previous')->andReturn('http://foo.com/bar');
        $response = $this->redirect->back();
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function _testAwayDoesntValidateTheUrl()
    {
        $response = $this->redirect->away('bar');
        $this->assertSame('bar', $response->getTargetUrl());
    }

    public function _testSecureRedirectToHttpsUrl()
    {
        $response = $this->redirect->secure('bar');
        $this->assertSame('https://foo.com/bar', $response->getTargetUrl());
    }

    public function _testAction()
    {
        $this->url->shouldReceive('action')->with('bar@index', [])->andReturn('http://foo.com/bar');
        $response = $this->redirect->action('bar@index');
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function _testRoute()
    {
        $this->url->shouldReceive('route')->with('home')->andReturn('http://foo.com/bar');
        $this->url->shouldReceive('route')->with('home', [])->andReturn('http://foo.com/bar');

        $response = $this->redirect->route('home');
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function _testSignedRoute()
    {
        $this->url->shouldReceive('signedRoute')->with('home', [], null)->andReturn('http://foo.com/bar?signature=secret');

        $response = $this->redirect->signedRoute('home');
        $this->assertSame('http://foo.com/bar?signature=secret', $response->getTargetUrl());
    }

    public function _testTemporarySignedRoute()
    {
        $this->url->shouldReceive('temporarySignedRoute')->with('home', 10, [])->andReturn('http://foo.com/bar?signature=secret');

        $response = $this->redirect->temporarySignedRoute('home', 10);
        $this->assertSame('http://foo.com/bar?signature=secret', $response->getTargetUrl());
    }

    public function _testItSetsAndGetsValidIntendedUrl()
    {
        $this->session->shouldReceive('put')->once()->with('url.intended', 'http://foo.com/bar');
        $this->session->shouldReceive('get')->andReturn('http://foo.com/bar');

        $result = $this->redirect->setIntendedUrl('http://foo.com/bar');
        $this->assertInstanceOf(Redirector::class, $result);

        $this->assertSame('http://foo.com/bar', $this->redirect->getIntendedUrl());
    }
}
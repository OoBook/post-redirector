# POST REDIRECTOR

<p align="center">
    <a href="https://packagist.org/packages/oobook/post-redirector"><img src="https://img.shields.io/packagist/dt/oobook/post-redirector" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/oobook/post-redirector"><img src="https://img.shields.io/packagist/v/oobook/post-redirector" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/oobook/post-redirector"><img src="https://img.shields.io/packagist/l/oobook/post-redirector" alt="License"></a> 
</p>

This package provides a convenient way to perform POST redirects within your Laravel application.  Unlike the standard redirector()->to() method, PostRedirector allows you to include additional data that gets submitted via a POST request before the redirection occurs.

## Installation
To install the package, run the following command in your terminal:

```
    composer require oobook/post-redirector
```

## Usage

### Using the PostRedirector Class:


The package provides a PostRedirector class that offers a similar API to the standard Laravel redirect helper. However, it allows you to specify additional data to be submitted in a POST request before the redirection:

```php
    Route::get('/old-url', function () {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ];

        return redirect()
            ->toWithPayload('/new-post-url', $data)
    });
```

In this example, the route redirects the user to /new-url and submits the provided $data array via a POST request before the redirection.

### Available Methods
- **toWithPayload(string $url, array|object $data)** : Sets url and the data to be submitted in the POST request

## Benefits

- **Flexibility**: Send additional data along with your redirects for processing on the target URL.
- **Maintainable Code**: Avoid complex logic to manually create forms for redirection.
- **Security**: Hidden form submission helps prevent sensitive data from being exposed in the URL.

## Contributing
We welcome contributions to this package. Please feel free to open an issue or pull request on the GitHub repository.

## License
This package is released under the MIT license. See the LICENSE file for more information.

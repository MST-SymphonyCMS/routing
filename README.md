# Routing

XML based frontend routing for [Symphony][1].

[1]: http://getsymphony.com



## Configuration

By default, the [extension][2] stores a `routes.xml` file in your [workspace][3] folder.

However, a new setting is also added to your `config.php`, where you can provide an alternative file path relative to the workspace folder.

[2]: http://getsymphony.com/learn/concepts/view/extensions/
[3]: http://getsymphony.com/learn/concepts/view/workspace/



## Attention

This extension completely replaces Symphony's default routing.

This means it will throw a [FrontendPageNotFoundException][4] and display a [404 page][5] for every path that's not defined in your `routes.xml` file, even if the page actually exists.

[4]: http://getsymphony.com/learn/api/2.3.3/core/frontendpagenotfoundexception/
[5]: http://getsymphony.com/learn/concepts/view/page-types/



## Caching

For better performance, the `routes.xml` file is only processed once and then cached using Symphony's [Cacheable API][6].

It's therefore necessary to refresh the cache after making changes in your `routes.xml` or `config.php`.

This can be done by simply [re-enabling the extension][7] under `System › Extensions`.

[6]: http://getsymphony.com/learn/api/2.3.3/core/cacheable/
[7]: http://getsymphony.com/learn/tasks/view/install-an-extension/



## Schema

The `routes.xml` schema is pretty simple.

    <routes>
        <route from="..." to="..." />
        <route from="..." to="...">
            <filter parameter="..." match="..." />
        </route>
    </routes>


### Routes

Each route is defined by a `<route>` element, which has two attributes, `@from` and `@to`.

These attributes contain pathes relative to your [root URL][8].

    <route from="/en/about-us/"  to="/about-us/" />
    <route from="/de/ueber-uns/" to="/about-us/" />

The `@from` path, which your users see in their browser address bar, is silently routed to an existing [page][9] path.

[8]: http://getsymphony.com/learn/concepts/view/parameters/
[9]: http://getsymphony.com/learn/concepts/view/pages/


### Parameters

Parameters are declared by a leading `:` and can be reused in the `@to` path.

    <route from="/:language/blog/:article/" to="/blog/:article/" />


### Filters

To match a parameter against a specific pattern, you can optionally provide a `<filter>` element for that parameter inside the `<route>` element.

A `<filter>` element also has two attributes, `@parameter` and `@match`.

    <route from="/:language/blog/:article/" to="/blog/:article/">

        <filter parameter=":language" match="[a-z]{2}" />

    </route>

The `@match` attribute can contain a [PCRE regex pattern][10].

By default, all parameters will be matched against the `[\w\.-]+` pattern.

[10]: http://php.net/manual/en/book.pcre.php

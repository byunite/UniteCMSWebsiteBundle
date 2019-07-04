# Create a symfony website based on unite cms

## Installation

### 0. Createa new Symfony Website
```bash
symfony new project
```

### 1. Composer install

Add this to your composer.json:

```json
{
    "require": {
        "unite/cms-website-bundle": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url":  "git@github.com:byunite/UniteCMSWebsiteBundle.git"
        }
    ]
}
```

### 2. Define required parameters:

In your services.yaml

```yaml
    parameters:
        sitesMapping: '%env(json:file:SITES_CONFIG_FILE)%'
        uniteCmsBaseUrl: '%env(UNITE_CMS_BASE_URL)%'
        baseUrl: '%env(BASE_URL)%'
        cacheClearSecret: '%env(CACHE_CLEAR_SECRET)%'
    
    services:
        # default configuration for services in *this* file
        _defaults:
            autowire: true      # Automatically injects dependencies in your services.
            autoconfigure: true # Automatically registers your services as commands, event subscribers, etc.
    
            bind:
                $cacheClearSecret: '%cacheClearSecret%'
    
        # makes classes in src/ available to be used as services
        # this creates a service per class whose id is the fully-qualified class name
        App\:
            resource: '../src/*'
            exclude: '../src/{DependencyInjection,Entity,Migrations,Tests,Kernel.php}'
    
        # controllers are imported separately to make sure services can be injected
        # as action arguments even if you don't extend any base controller class
        App\Controller\:
            resource: '../src/Controller'
            tags: ['controller.service_arguments']
```


### 3. Enable bundle

Enable bundle In your bundles.php

```php
<?php
    return [
        Unite\CMSWebsiteBundle\UniteCMSWebsiteBundle::class => ['all' => true],
    ];
``` 

### 4. Create a site controller
```php
<?php

namespace App\Controller;

use Unite\CMSWebsiteBundle\Model\Site;
use Unite\CMSWebsiteBundle\Services\BlockTypeManager;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class SiteController extends AbstractController
{

    /**
     * @Route("/{slug}", name="site", defaults={"slug" = ""})
     * @ParamConverter("site")
     *
     * @param BlockTypeManager $blockTypeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param Environment $twig
     * @param Site $site
     * @param string $slug
     *
     * @return Response
     */
    public function index(BlockTypeManager $blockTypeManager, LoggerInterface $logger, Environment $twig, Site $site, string $slug)
    {
        $blocks = [];
        $template = 'index.html.twig';

        try {
            $site->setCurrentSlug($slug);
            $blocks = $blockTypeManager->renderBlocks($site);
        } catch (NotFoundHttpException $exception) {
            $logger->error($exception->getMessage(), ['exception' => $exception]);
            $template = '404.html.twig';
        }

        return $this->render(join('/', ['site', $site->getTemplate(), $template]), [
            'site' => $site,
            'blocks' => $blocks,
        ]);
    }
}

```

### 5. Create a cache controller
```php
<?php

namespace App\Controller;

use Unite\CMSWebsiteBundle\Model\Site;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CacheController extends AbstractController
{

    /**
     * @Route("/_cache/clear/site")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Contracts\Cache\TagAwareCacheInterface $cache
     *
     * @param Site $site
     * @param string $cacheClearSecret
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function index(Request $request, TagAwareCacheInterface $cache, Site $site, string $cacheClearSecret)
    {
        if($cacheClearSecret !== $request->headers->get('Authorization')) {
            throw new AccessDeniedHttpException('Invalid secret.');
        }

        $cache->invalidateTags([$site->getCacheKey()]);
        return new Response('Cache cleared');
    }
}

```

### 6. Create custom templates to render your website

templates/sites/generic/base.html.twig: 
```twig 
<!DOCTYPE html>
<html lang="de">
    <head>
        {% block meta %}
            <meta charset="UTF-8">
            <meta name="robots" content="index, follow">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
            <meta http-equiv="x-ua-compatible" content="ie=edge">
            {% include '@UniteCMSWebsite/partials/_seo-meta-tags.html.twig' with { site: site } %}
        {% endblock %}
        {% block stylesheets %}
            {{ encore_entry_link_tags('app', null, 'generic') }}
        {% endblock %}
    </head>
    <body>
        {% block body %}{% endblock %}
        {% block javascripts %}
            {{ encore_entry_script_tags('app', null, 'generic') }}
        {% endblock %}
    </body>
</html>
``` 

templates/sites/generic/html.twig: 
```twig
{% extends 'site/generic/base.html.twig' %}
{% block body %}

    {% include 'site/generic/partials/_navigation.html.twig' with { site: site } %}

    <section class="site-content">
        {% include '@UniteCMSWebsite/partials/_blocks.html.twig' with { site: site } %}
    </section>

    {% include 'site/generic/partials/_footer.html.twig' with { site: site } %}

{% endblock %}
``` 

templates/sites/generic/404.html.twig:
```twig
{% extends 'site/generic/base.html.twig' %}
{% block body %}

    {% include 'site/generic/partials/_navigation.html.twig' with { site: site } %}

    <section class="content-block error-404">
        <h2>Seite konnte nicht gefunden werden.</h2>
        <a href="{{ url('site', {slug: site.pages.first.slug}) }}">Zurück zur Startseite</a>
    </section>

    {% include 'site/generic/partials/_footer.html.twig' with { site: site } %}

{% endblock %}
``` 

sites/templates/generic/partials/_navigation.html.twig:
```twig
{% if site.pages|length > 0 %}
    <header class="site-header navbar p-fixed bg-white">
        <section class="navbar-section">
            <h1>
                <a href="{{ url('site', { slug: site.pages.first.slug }) }}" class="navbar-brand mr-2">{{ site }}</a>
            </h1>
        </section>
        <section class="navbar-section nav-container">
            <button class="btn btn-action nav-toggle" tabindex="0">
                <i class="icon icon-menu"></i>
            </button>
            <nav>
                {% for page in site.pages %}
                    <a class="{{ ['btn', page.menuButton ?? 'btn-link', (site.currentPage == page ? 'active' : null)]|join(' ')|trim }}" href="{{ url('site', { slug: page.slug }) }}">{{ page }}</a>
                {% endfor %}
            </nav>
        </section>
    </header>
{% endif %}
``` 

sites/templates/generic/partials/_footer.html.twig:
```twig
<footer></footer>
``` 
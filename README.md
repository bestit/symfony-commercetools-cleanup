# symfony-commercetools-cleanup

Shell app to delete entries out of your commercetools database matching predicates out of your config.

## Installation

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require bestit/commercetools-cleanup
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [];

        // ...
        
        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new BestIt\CtCleanUpBundle\BestItCtCleanUpBundle();
        }
        
        // ...
    }

    // ...
}
```

### Step 3: Configure the bundle

```yaml
# Default configuration for "BestItCtCleanUpBundle"
best_it_ct_clean_up:
    commercetools_client: # Required
        id:                   ~ # Required
        secret:               ~ # Required
        project:              ~ # Required
        scope:                ~ # Required

    # Please provide the service id for your logging service.
    logger:               ~
    predicates:

        # Define your category predicates (https://dev.commercetools.com/http-api.html).
        category:             []

        # Define your customer predicates (https://dev.commercetools.com/http-api.html).
        customer:             []

        # Define your product predicates (https://dev.commercetools.com/http-api.html).
        product:              []
```

#### Example:

```yaml
best_it_ct_clean_up:
    # Reuse your default credentials from the commercetools sdk
    commercetools_client:
        id: '%commercetools.client_id%'
        project: '%commercetools.project%'
        secret: '%commercetools.client_secret%'
        scope: '%commercetools.scope%'
    # Use your logger specially used for the cleanup    
    logger: 'monolog.logger.%cleanup.log_channel%'
    predicates:
        # The predicates for customers which should be deleted. The array elements are combined to an or query.
        customer:
            - 'externalId is defined'
            - 'lastName="foobar" and email="test@example.com"
        product:
            # Delete every product which is not published and older than 5 minutes.
            - 'masterData(published=false) and lastModifiedAt <= "{{- 5 minutes}}"'
```

**You can provide a [strtotime](http://php.net/strtotime)-compatible string enclosed with _{{ and }}_ to get dynamic date checks.**

### Step 4: Use the command

```console
$ php bin/console bestit:cleanup -s
```

If you provide the option -s/--simulate no row will be deleted, the total count will just be outputted.

## Further Todos

* More Unittesting
* Refactor the command
* Add more Types
* Add Exceptions in the delete process

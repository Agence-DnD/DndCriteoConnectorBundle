CriteoConnectorBundle
=====================

Criteo Connector for Akeneo PIM >= 1.5

This connector allows you to export your products into XML format for Criteo.

It will create a new "Criteo" export type in which you can map your Akeneo attributes to the mandatory attributes needed for Criteo exports. The connector will generate a XML file with the correct header for Criteo integration.

## Video demonstration

You can watch the webinar dedicated to the extension :

[English version](https://www.youtube.com/watch?v=v9DY-QRZ33c)

[French version](https://www.youtube.com/watch?v=bw8J1vM7ebs)

## Requirements

| CriteoConnectorBundle   | Akeneo PIM Community Edition |
|:-----------------------:|:----------------------------:|
| v2.0.*                  | v3.*                         |
| v1.3.*                  | v2.*                         |
| v1.2.*                  | v1.7.*                       |
| v1.1.*                  | v1.6.*                       |
| v1.0.*                  | v1.5.*                       |

## Connector installation on Akeneo PIM

If it's not already done, install [Akeneo PIM](https://github.com/akeneo/pim-community-standard).

Get composer (with command line):
```console
$ cd /my/pim/installation/dir
$ curl -sS https://getcomposer.org/installer | php
```

Then, install DnD-CriteoConnectorBundle with composer:

In your ```composer.json```, add the following code dependending on your Akeneo PIM version:

* In `require`:

```json
{
    "agencednd/criteo-connector-bundle": "1.3.*"
}
```

Next, enter the following command line:
```console
$php composer.phar require agencednd/criteo-connector-bundle
```

Enable the bundle in ```app/AppKernel.php``` file, in the ```registerBundles``` function, before the line ```return $bundles```:
```php
$bundles[] = new Dnd\Bundle\CriteoConnectorBundle\DndCriteoConnectorBundle();
```
Add the route in ```app/routing.yml```file, under the _pim_reference_data_ node:
```yml
dnd_criteo_connector:
    prefix: /criteo-connector
    resource: "@DndCriteoConnectorBundle/Resources/config/routing.yml"
```

## Configuration

###### Create symlink for your images

If you manage your images in the PIM, **you must create a symlink of the _app/file_storage folder_ into the _web folder_** :
```console
$ ln -s /my/pim/installation/dir/web/file_storage /my/pim/installation/dir/app/file_storage
```

###### Export

* Go to _Spread_ > _Export profiles_ and then create your _CriteoConnectorBundle_ export products for Criteo profile
* Map your attributes to Criteo attributes (leave empty if you don't use one of them, see this [Article](http://support.datafeedwatch.com/hc/en-us/articles/200106112-Criteo) to know the required attributes)

## About us
Founded by lovers of innovation and design, [Agence Dn'D](https://www.dnd.fr) assists companies in the creation and development of customized digital (open source) solutions for web and E-commerce since 2004.

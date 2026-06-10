# Breadcrumb


The Breadcrumb Data Source only provides the actual page hierarchy of the current page.

A homepage/frontpage item is intentionally not included automatically, since projects may use different labels, icons or structures for the first breadcrumb item.

It is recommended to prepend the root item directly in the XSLT template, for example as:

- a text label (Home, Start, Dashboard, ...)
- an icon
- or a project-specific navigation element.

Dynamic entries such as blog posts, products or news articles can also be appended directly in the template using the current Data Source output.

## Installation

1. Upload the 'breadcrumb' folder in this archive to your Symphony 'extensions' directory.
2. Enable it by selecting the "Breadcrumb", choose "Enable/Install" from the with-selected menu, then click Apply.
3. Add the "Breadcrumbs" Data Source to your page

## Example XML

```xml
<breadcrumb>
    <page path="about">About</page>
    <page path="about/contact">Contact</page>
    <page path="about/contact/office">Our Office</page>
</breadcrumb>
```

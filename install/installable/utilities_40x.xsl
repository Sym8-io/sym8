<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:variable name="root" select="normalize-space(/data/params/root)" />
<xsl:variable name="current-page" select="normalize-space(/data/params/current-page)" />
<xsl:variable name="current-path" select="normalize-space(/data/params/current-path)" />

<xsl:output method="xml"
    doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
    doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
    omit-xml-declaration="yes"
    encoding="UTF-8"
    indent="yes" />

<xsl:template match="/">
<html lang="en" dir="ltr">
    <head>
        <title>
            <xsl:choose>
                <xsl:when test="$current-page = '403'">
                    <xsl:text>Access forbidden</xsl:text>
                </xsl:when>
                <xsl:when test="$current-page = '404'">
                    <xsl:text>Page not found</xsl:text>
                </xsl:when>
            </xsl:choose>
        </title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="format-detection" content="telephone=no" />
        <meta name="color-scheme" content="dark light" />
    </head>
    <body>
        <p>
            <code>
                <xsl:choose>
                    <xsl:when test="$current-page = '403'">
                        <xsl:text>403 – Access forbidden</xsl:text>
                    </xsl:when>
                    <xsl:when test="$current-page = '404'">
                        <xsl:text>404 – Page not found</xsl:text>
                    </xsl:when>
                </xsl:choose>
            </code>
        </p>
        <xsl:choose>
            <xsl:when test="$current-page = '403'">
                <p><code>You don't have permission to access the requested resource.</code></p>
            </xsl:when>
            <xsl:when test="$current-page = '404'">
                <p><code>Sorry, the requested page could not be found.</code></p>
            </xsl:when>
        </xsl:choose>
        <p><code><a href="{$root}/">homepage</a></code></p>
    </body>
</html>
</xsl:template>

</xsl:stylesheet>

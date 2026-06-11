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
                <xsl:when test="$current-page = '400'">
                    <xsl:text>Bad request</xsl:text>
                </xsl:when>
                <xsl:when test="$current-page = '401'">
                    <xsl:text>Unauthorized</xsl:text>
                </xsl:when>
                <xsl:when test="$current-page = '403'">
                    <xsl:text>Access forbidden</xsl:text>
                </xsl:when>
                <xsl:when test="$current-page = '404'">
                    <xsl:text>Page not found</xsl:text>
                </xsl:when>
                <xsl:when test="$current-page = '429'">
                    <xsl:text>Too many requests</xsl:text>
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
                    <xsl:when test="$current-page = '400'">
                        <xsl:text>400 – Bad request</xsl:text>
                    </xsl:when>
                    <xsl:when test="$current-page = '401'">
                        <xsl:text>401 – Unauthorized</xsl:text>
                    </xsl:when>
                    <xsl:when test="$current-page = '403'">
                        <xsl:text>403 – Access forbidden</xsl:text>
                    </xsl:when>
                    <xsl:when test="$current-page = '404'">
                        <xsl:text>404 – Page not found</xsl:text>
                    </xsl:when>
                    <xsl:when test="$current-page = '429'">
                        <xsl:text>429 – Too many requests</xsl:text>
                    </xsl:when>
                </xsl:choose>
            </code>
        </p>
        <xsl:choose>
            <xsl:when test="$current-page = '400'">
                <p><code>The request could not be processed due to invalid or malformed input.</code></p>
            </xsl:when>
            <xsl:when test="$current-page = '401'">
                <p><code>Authentication is required to access the requested resource.</code></p>
            </xsl:when>
            <xsl:when test="$current-page = '403'">
                <p><code>You don't have permission to access the requested resource.</code></p>
            </xsl:when>
            <xsl:when test="$current-page = '404'">
                <p><code>Sorry, the requested page could not be found.</code></p>
            </xsl:when>
            <xsl:when test="$current-page = '429'">
                <p><code>Too many requests were received. Please try again later.</code></p>
            </xsl:when>
        </xsl:choose>
        <p><code><a href="{$root}/">homepage</a></code></p>
    </body>
</html>
</xsl:template>

</xsl:stylesheet>

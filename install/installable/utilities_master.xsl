<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:variable name="root" select="normalize-space(/data/params/root)" />
<xsl:variable name="current-page" select="normalize-space(/data/params/current-page)" />
<xsl:variable name="current-path" select="normalize-space(/data/params/current-path)" />
<xsl:variable name="website-name" select="normalize-space(/data/params/website-name)" />
<xsl:variable name="page-title" select="normalize-space(/data/params/page-title)" />
<xsl:variable name="cookie-pass" select="normalize-space(/data/params/cookie-pass)" />

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
                <xsl:when test="$current-page = 'home'">
                    <xsl:value-of select="$website-name" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$page-title" />
                    <xsl:text> | </xsl:text>
                    <xsl:value-of select="$website-name" />
                </xsl:otherwise>
            </xsl:choose>
        </title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="format-detection" content="telephone=no" />
        <meta name="color-scheme" content="dark light" />
        <link rel="stylesheet" href="{$root}/app/css/pico.pink.min.css" />
        <link rel="stylesheet" href="{$root}/app/css/sym8.css" />
        <link href="{$root}/favicon.ico" rel="shortcut icon" type="image/x-icon" />
    </head>
    <body>
        <header class="container">
            <h1>
                <xsl:choose>
                    <xsl:when test="$current-page = 'home'">
                        <xsl:value-of select="$website-name" />
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="$page-title" />
                    </xsl:otherwise>
                </xsl:choose>
            </h1>
        </header>
        <main class="container">
            <p>Start something new from scratch.</p>
        </main>
        <footer class="container">
            <xsl:if test="$cookie-pass">
                <p>Take me to the <a href="{$root}/symphony/">Symphony backend</a>.</p>
            </xsl:if>
        </footer>
    </body>
</html>
</xsl:template>

</xsl:stylesheet>

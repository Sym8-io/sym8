<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
  omit-xml-declaration="no"
  encoding="UTF-8"
  indent="yes" />

<xsl:template match="/">
  <data>
    <title><xsl:value-of select="/data/params/page-title"/></title>
  </data>
</xsl:template>

</xsl:stylesheet>

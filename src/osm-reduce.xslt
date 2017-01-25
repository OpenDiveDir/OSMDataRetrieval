<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <!--
    Transformation goals:
    - Copy the <node> tags referenced by a way's <nd> tags into the <way> tag.
    - If a <node> tag is made redundant, remove it.
    - If a <way> tag has a <tag> attribute with a k value of "name", copy that
      value of that tag into the <way> attribute.
    -->

    <!-- 
    Provide a generic pass for all non-identified tags.
    -->
    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>

    <xsl:template match="osm">
        <!-- Only process XML files which contain a NODE or a WAY xml node. -->
        <!-- Sources without a node or a way produce an empty file. -->
        <xsl:if test="//node|//way">

            <!-- Add a comment to the output, to note that it's not the original. -->
            <xsl:comment>
    Transformed for open-dive-directory.
</xsl:comment>

            <xsl:copy>
                <!-- Copy the attributes of the top-level osm node. -->
                <xsl:copy-of select="attribute::*" />

                <xsl:apply-templates />
            </xsl:copy>
        </xsl:if>
    </xsl:template>

    <xsl:template match="//way">
        <xsl:text>&#xa;&#xa;</xsl:text>
            <xsl:comment>
                <xsl:choose>
                    <xsl:when test="current()/tag[@k = 'name']"> Dive site: <xsl:value-of select="current()/tag[@k = 'name']/@v" /> </xsl:when>
                    <xsl:otherwise> Unidentified dive site. </xsl:otherwise>
                </xsl:choose>
            </xsl:comment>
        <xsl:text>&#xa;</xsl:text>

        <way>
            <!-- Copy in the attributes -->
            <xsl:copy-of select="attribute::*" />

            <!-- If there's a <tag k="name">, copy the value as an attribute. -->
            <xsl:if test="current()/tag[@k = 'name']">
                <xsl:attribute name="name">
                    <xsl:value-of select="current()/tag[@k = 'name']/@v" />
                </xsl:attribute>
            </xsl:if>

            <xsl:apply-templates />
        </way>
    </xsl:template>

    <!-- Copy each <nd> reference from the target <node>. -->
    <xsl:template match="//way/nd[@ref]">
        <xsl:copy-of select="//node[@id=current()/@ref]"/>
    </xsl:template>

    <!-- Remove each <node> attached to a <way>. -->
    <xsl:template match="//node[@id=//way/nd/@ref]"></xsl:template>

    <!-- If there's a <tag k="name">, copy the value as an attribute. -->
    <xsl:template match="//node[tag/@k = 'name']">
        <xsl:element name="{local-name()}">
            <xsl:copy-of select="attribute::*" />

            <xsl:attribute name="name">
                <xsl:value-of select="current()/tag[@k = 'name']/@v" />
            </xsl:attribute>

            <xsl:apply-templates />
        </xsl:element>
    </xsl:template>

</xsl:stylesheet>

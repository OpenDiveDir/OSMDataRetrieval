<?php
/**
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
*/

include_once 'phing/filters/BaseParamFilterReader.php';
include_once 'phing/filters/ChainableReader.php';

/**
 * Transforms XML to GeoJSON
 */
class GeoJsonise extends BaseParamFilterReader implements ChainableReader
{

    /**
     * Whether XML file has been transformed.
     * @var boolean
     */
    private $processed = false;

    /**
     * Reads stream, applies XSLT and returns resulting stream.
     * @param null $len
     * @throws BuildException
     * @return string         transformed buffer.
     */
    public function read($len = null)
    {

        if (!class_exists('SimpleXMLElement')) {
            throw new BuildException("Could not find the SimpleXMLElement class. Make sure PHP has been compiled/configured to support SimpleXML.");
        }

        if ($this->processed === true) {
            return -1; // EOF
        }

        if (!$this->getInitialized()) {
            $this->_initialize();
            $this->setInitialized(true);
        }

        // Read XML
        $_xml = null;
        while (($data = $this->in->read($len)) !== -1) {
            $_xml .= $data;
        }

        if ($_xml === null) { // EOF?

            return -1;
        }

        if (empty($_xml)) {
            $this->log("XML file is empty!", Project::MSG_WARN);

            return ''; // return empty string, don't attempt to apply XSLT
        }

        $this->log(
            "Tranforming XML " . $this->in->getResource(),
            Project::MSG_VERBOSE
        );

        $out = '';
        try {
            $out = $this->process($_xml);
            $this->processed = true;
        } catch (IOException $e) {
            throw new BuildException($e);
        }

        return $out;
    }

    // {{{ method _ProcessXsltTransformation($xml, $xslt) throws BuildException
    /**
     * Try to process the XSLT transformation
     *
     * @param string $xml XML to process.
     * @param string $xsl XSLT sheet to use for the processing.
     *
     * @return string
     *
     * @throws BuildException On XSLT errors
     */
    protected function process($xml)
    {
        // Initialise an empty array to store each feature.
        $feature_collection = array();

        $osm_xml = new SimpleXMLElement($xml);
        foreach ($osm_xml->xpath('//osm/node | //osm/way') as $feature) {
            $feature_collection[] = $this->parseFeature($feature);
        }

        // Only return results if at least 1 feature exists.
        if (count($feature_collection)) {
            $result = array(
                'type' => 'FeatureCollection',
                'features' => $feature_collection,
            );
            $result = json_encode($result);
            return $result;
        }
        else {
            return '';
        }
    }
    
    protected function parseFeature(SimpleXMLElement $feature) {
        $feature_type = $feature->getName();
        switch ($feature->getName()) {
            case 'way':
                return $this->parseFeatureWay($feature);

            case 'node':
                return $this->parseFeatureNode($feature);
        }
    }

    protected function parseFeatureWay(SimpleXMLElement $feature) {

        $attributes = (array) $feature->attributes();
        $attributes = $attributes['@attributes'];


        $way = array(
            'type' => 'Feature',
            'geometry' => array(
                'type' => 'LineString',
                'coordinates' => array(),
            ),
            'properties' => $attributes,
        );
        $way['properties']['tags'] = array();
        $way['properties']['nodes'] = array();


        // Iterate each child node (usually either a <tag> or <node> entry).
        foreach ($feature as $node_name => $value) {
            $attrs = (array) $value->attributes();
            $attrs = $attrs['@attributes'];
            switch ($node_name) {
                case 'tag':
                    $key = $attrs['k'];
                    $val = $attrs['v'];
                    $way['properties']['tags'][$key] = $val;
                    break;

                case 'node':
                    $way['properties']['nodes'][] = $attrs;
                    $way['geometry']['coordinates'][] = array((float)$attrs['lon'], (float)$attrs['lat']);
                    break;
            }
        }
        return $way;
    }

    protected function parseFeatureNode(SimpleXMLElement $feature) {
        // Nodes just have attributes, and child <tag> nodes.
        $attributes = (array) $feature->attributes();

        $node = array(
            'type' => 'Feature',
            'geometry' => array(
                'type' => 'Point',
                'coordinates' => array(
                    (float)$attributes['@attributes']['lon'],
                    (float)$attributes['@attributes']['lat']
                ),
            ),
            'properties' => $attributes['@attributes'],
        );
        $node['properties']['tags'] = array();

        // Check for child <tag> nodes: attach them to the properties.
        foreach ($feature as $node_name => $value) {
            $child_attrs = (array) $value->attributes();
            $child_attrs = $child_attrs['@attributes'];
            switch ($node_name) {
                case 'tag':
                    $key = $child_attrs['k'];
                    $val = $child_attrs['v'];
                    $node['properties']['tags'][$key] = $val;
                    break;
            }
        }

        return $node;
    }


    /**
     * Creates a new XsltFilter using the passed in
     * Reader for instantiation.
     *
     * @param Reader A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     *
     * @return XsltFilter A new filter based on this configuration, but filtering
     *                    the specified reader
     */
    public function chain(Reader $reader)
    {
        $newFilter = new GeoJsonise($reader);
        $newFilter->setProject($this->getProject());
        return $newFilter;
    }

    /**
     * Parses the parameters to get stylesheet path.
     */
    private function _initialize()
    {
    }
}

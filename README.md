These scripts request data from open-street-map and build a collection of XML
files.

There are 3 categories (layers) of data, made up of 4 queries:
- Dive clubs (club=scuba_diving)
- Dive sites (sport=scuba_diving)
- Dive shops (amenity=dive_centre or shop=scuba_diving)

Each query requires a separate HTTP request (because of the limitations of the
XAPI lookup).


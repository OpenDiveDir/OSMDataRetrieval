# About Open Dive Directory

Open Dive Directory is a product that uses Open Street Map's data to display
locations of scuba diving sites, shops, services, and clubs.

# About this tool

This tool retrieves scuba-related geodata from OSM XAPI servers, and transforms
it into a standard GeoJSON format, for use by Open Dive Directory systems.

There are 3 categories (layers) of data, made up of 4 queries:
- Dive clubs (club=scuba_diving)
- Dive sites (sport=scuba_diving)
- Dive shops (amenity=dive_centre or shop=scuba_diving)

Each query requires a separate HTTP request (because of the limitations of the
XAPI lookup).

Queries are geographically limited to a region measuring 10º of longitude and
latitude.

# Requirements

This tool requires Phing, a PHP port of Ant.

# License

This tool is licensed under GPL v3.

It also packages 3 PEAR libraries under the classes/vendor directory.
These are Copyright to their original authors, and shared under their
respective BSD licenses.

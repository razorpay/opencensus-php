/**
 * Effective connection type (ECT) refers to the measured network performance,
 * returning a cellular connection type, like 3G, even if the actual connection
 * is tethered broadband or WiFi, based on the time between the browser requesting
 * a page and effective type of the connection
 * @returns {String} slow-2g, 2g, 3g, 4g
 */
const getConnectionType = () => {
  return navigator.connection ? navigator.connection.effectiveType : null;
};

export default getConnectionType;

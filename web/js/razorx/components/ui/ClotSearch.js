/*
  Description: Multiple iterations of react-render due to fast changing frontend search-filter jams view,
  so to resolve this issue, ClotSearch will make use of blood clotting phenomenon.

  Functionality:
 - Any new search will enter into new clot cycle.
 - Only Clotted search will take into affect to re-render. Clot happens after eg- 250ms(or custom) cycle.
 - So, any change in search query before this 250ms will flush the existing clot before entering new clot cycle.

 */

export default function ClotSearch(clotTime = 250) {
  this.clotCycle = null;
  this.isClotCycleOn = false;

  // Start fresh clot cycle
  this.startClotCycle = function(cb) {
    if (this.isClotCycleOn) {
      this.flushCurrentClot();
    }

    this.isClotCycleOn = true;

    this.clotCycle = setTimeout(() => {
      cb();
      this.flushCurrentClot();
    }, clotTime);
  };

  // Remove existing clot cycle
  this.flushCurrentClot = function() {
    clearTimeout(this.clotCycle);
    this.isClotCycleOn = false;
  };
}

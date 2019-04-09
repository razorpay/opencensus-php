import Collection from 'model/collection';

export default class SubmerchantCollection extends Collection {
  update(newConfig, submerchantId) {
    if (newConfig && submerchantId) {
      this.items.forEach(({ submerchant }, index) => {
        if (submerchant.id === submerchantId) {
          this.items[index].config = { ...newConfig };
        }
      });
    }
    return newConfig;
  }
}

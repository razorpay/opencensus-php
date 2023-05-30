import card from './card';
import emi from './emi';
import netbanking from './netbanking';
import upi from './upi';
import wallet from './wallet';
import paylater from './paylater';
import international from './international';
import MealCard from './mealcard';

function getIconFn(iconName) {
  switch (iconName) {
    case 'card':
      return card;
    case 'netbanking':
      return netbanking;
    case 'upi':
      return upi;
    case 'emi':
      return emi;
    case 'wallet':
      return wallet;
    case 'paylater':
      return paylater;
    case 'international':
      return international;
    case 'mealcard':
      return MealCard;
    default:
      return null;
  }
}

export const getIcon = (iconName) => {
  const iconFn = getIconFn(iconName);
  return iconFn && iconFn();
};

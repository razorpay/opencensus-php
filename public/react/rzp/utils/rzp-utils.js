export function titleCase(sentence = '') {
  return sentence.split(' ').map((word) => word.charAt(0).toUpperCase() + word.substr(1)).join(' ')
}

import moment from 'moment'

export function titleCase(sentence = '') {
  return sentence.split(' ').map((word) => word.charAt(0).toUpperCase() + word.substr(1)).join(' ')
}

export function makeArray(obj) {
  if (!obj) { return [] }
  return Array.isArray(obj) ? obj : [obj]
}

export function isBlank(obj) {
  if (!obj) return !obj

  if(typeof obj === 'object') {
    return !Object.keys(obj).length
  }

  if (typeof obj === 'string') {
    obj = obj.trim()
  }
  return !obj
}

export const findBy = (array, prop, value) => {
  return array.find((item) => {
    return item[prop] === value
  })
}

export const filterBy = (array, prop, value) => {
  return array.filter((item) => {
    return item[prop] === value
  })
}

export const mapBy = (array, prop) => {
  return array.map((item) => {
    return item[prop]
  })
}

export const pipe = (...funcs) => {
  let first = funcs.shift()
  return (...args) => {
    return funcs.reduce((returnVal, currentFn) => {
      return currentFn(returnVal)
    }, first(...args))
  }
}

export const normalizeDate = date => (moment(date).format('D/M/Y'))

import { Map } from 'extendable-immutable'

export default class BaseModel extends Map {
  resourceIdField = 'id'

  constructor() {
    super(...arguments)
  }

  get isNew() {
    return !this.get(this.resourceIdField)
  }

  getResourceUrl() {
    if (this.isNew) {
      return this.resourceUrl
    }
    return `${this.resourceUrl}/${this.get(this.resourceIdField)}`
  }

  getResourceMethod() {
    return this.isNew ? 'post' : 'put'
  }

  getResourceUrlAndMethod() {
    return [ this.getResourceUrl() , this.getResourceMethod() ]
  }

  serialize() {
    let resourceProperties = this.resourceProperties
    let serializedModel = {}

    for (let i = 0, len = resourceProperties.length; i < len; i++) {
      let prop = resourceProperties[i]
      serializedModel[prop] = this.serializeProperty(prop)
    }

    return serializedModel
  }

  serializeProperty(prop) {
    return this.get(prop) || this[prop]
  }

  deserialize(json) {
    let deserializedInstance = this.withMutations((instance) => {
      for (let prop in json) {
        instance.deserializeProperty(prop, json[prop])
      }
    })
    return deserializedInstance
  }

  deserializeProperty(prop, value) {
    this.set(prop, value)
  }
}

import { Factory } from 'dfaqapi'
import { faker } from 'dfaqapi'

export default class PlansFactory extends Factory {
  id = faker.internet.password(15, false, undefined, 'item_')
  name = faker.random.word()
  description = faker.random.words(7)
  currency = 'INR'
  rate = faker.commerce.price(99.99, 499.99)
}

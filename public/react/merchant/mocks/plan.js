import { Factory } from 'dfaqapi'
import { faker } from 'dfaqapi'

export default class PlansFactory extends Factory {
  id = faker.internet.password(15, false, undefined, 'plan_')
  name = faker.random.word()
  description = faker.random.words(7)
  currency = 'INR'
  interval = faker.utils.oneOf(['daily', 'weekly', 'monthly'])
  interval_count = faker.random.number({min: 1, max: 4})
  amount = faker.commerce.price(99.99, 499.99)
}

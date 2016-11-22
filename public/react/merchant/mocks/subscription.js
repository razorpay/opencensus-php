import { Factory } from 'dfaqapi'
import { faker } from 'dfaqapi'

export default class SubscriptionsFactory extends Factory {
  id = faker.internet.password(15, false, undefined, 'subs_')
  status = faker.utils.oneOf(['created', 'active', 'failed'])
  token_id = faker.internet.password(20, false, undefined, 'token_')
  quantity = faker.random.number({min: 1, max: 3})
  processed_at = Math.round(faker.date.recent(faker.random.number({min: 30, max: 50})).getTime()/1000)
  charge_at = Math.round(faker.date.between(new Date(), '12 Dec 2016').getTime()/1000)
  amount = faker.commerce.price(99.99, 499.99)

  customer = this.belongsTo('customer')
  customer_id = this.customer.id
  plan_id = this.belongsTo('plan').id
}

import { Factory } from 'dfaqapi'
import { faker } from 'dfaqapi'

export default class InvoiceFactory extends Factory {
  id = faker.internet.password(15, false, undefined, 'inv_')
  status = faker.utils.oneOf(['draft', 'issued', 'paid', 'expired'])
  invoice_number = `INV-${faker.random.number({min: 100, max: 199})}`
  total_amount = faker.commerce.price(299, 15499)
  invoice_date = Math.round(faker.date.recent(faker.random.number({min: 30, max: 50})).getTime()/1000)
  due_date = Math.round(faker.date.between(new Date(), '12 Dec 2016').getTime()/1000)
  customer = this.belongsTo('customer')
  customer_id = this.customer.id
}

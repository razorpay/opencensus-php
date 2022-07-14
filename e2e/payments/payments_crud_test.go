package payments

import (
	"fmt"
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"reflect"
	"strings"
	"testing"
	"time"
)

type PaymentsAPITestSuite struct {
	itf.Suite
}

func (s *PaymentsAPITestSuite) TestPaymentCreateAndFetch() {
	s.T().Skip("Test is intermittently failing because of 400 error")

	jsonString := "{\"amount\":\"500\",\"currency\":\"INR\",\"card\":{\"number\":\"4012001038443335\"," +
		"\"name\":\"Harshil\",\"cvv\":\"566\",\"expiry_month\":\"12\", \"expiry_year\":\"2024\"}," +
		" \"notes\":{\"key\":\"himgang\"}, \"description\":\"random_description_himgang_postman\" ," +
		"\"email\":\"a@b.com\",\"contact\":\"9999999999\",\"bank\":\"IDIB\",\"method\":\"card\",\"fee\":\"11\"}"
	for _, scenario := range CreatePaymentTests {
		s.Run(scenario.description, func() {
			//fmt.Println(jsonString)
			//			using string json because nested struct is not being converted properly by json library used.
			paymentId := CreatePayment(s.T(), jsonString)
			fmt.Printf(" paymentId Created %v \n", paymentId)
			startTimestamp := time.Now().UTC().UnixNano()
			fmt.Println(startTimestamp)
			paymentIdFetched := false
			payRes := FetchPayment(s.T())
			for key, element := range payRes {
				if key == "items" {
					switch reflect.TypeOf(element).Kind() {
					case reflect.Slice:
						s := reflect.ValueOf(element)
						for i := 0; i < s.Len(); i++ {
							payment := s.Index(i)
							paymentObject := payment.Interface().(map[string]interface{})
							for k, v := range paymentObject {
								if k == "id" {
									ss := strings.Split(v.(string), "_")
									if paymentId == ss[len(ss)-1] {
										paymentIdFetched = true
										//fmt.Printf("%v ,", ss[len(ss)-1])
									}
								}
							}
						}
					}
				}
			}
			endTimestamp := time.Now().UTC().UnixNano()
			fmt.Println(endTimestamp)
			verifyFetchPaymentResponse(s.T(), endTimestamp-startTimestamp, 4000000000)
			assert.True(s.T(), paymentIdFetched)
		})
	}
}

func verifyFetchPaymentResponse(t *testing.T, actual int64, expected int64) {
	assert.GreaterOrEqual(t, expected, actual)
}

func TestPaymentsAPI(t *testing.T) {
	suite.Run(t, &PaymentsAPITestSuite{Suite: itf.NewSuite(itf.WithTags([]string{"payments"}), itf.WithPriority(itf.PriorityP0))})
}

package payments

import (
	"encoding/json"
	"fmt"
	"github.com/razorpay/api/e2e"
	"golang.org/x/net/html"
	"net/http"
	"strings"
	"testing"
)

func CreatePayment(t *testing.T, reqString string) string {
	Initialize(t)
	var jsonMap map[string]interface{}
	json.Unmarshal([]byte(reqString), &jsonMap)
	//fmt.Println(" payment create request")
	//fmt.Println(jsonMap)

	obj := paymentsHost.POST("/v1/payments/create").
		WithBasicAuth(e2e.Config.Payments.Username, e2e.Config.Payments.Password).
		WithHeaders(header).
		WithJSON(jsonMap).
		Expect().
		Status(http.StatusOK).Body()
	//fmt.Println(reflect.TypeOf(obj))
	doc, err := html.Parse(strings.NewReader(obj.Raw()))
	res := PaymentCreateResponse{}
	if err != nil {
		fmt.Printf("%s", err)
	} else {
		parse_html(doc, &res)
	}
	//fmt.Println(" payment create API response: ")
	//fmt.Printf("%+v", obj)
	//fmt.Println("returning payment Id")
	//fmt.Printf("%+v", res)
	return res.Id
}

func FetchPayment(t *testing.T) map[string]interface{} {
	Initialize(t)
	obj := paymentsHost.GET("/v1/payments").
		WithBasicAuth(e2e.Config.Payments.Username, e2e.Config.Payments.Password).
		WithHeaders(header).
		Expect().
		Status(http.StatusOK).
		Body()
	//fmt.Println(" ")
	var jsonMap map[string]interface{}
	json.Unmarshal([]byte(obj.Raw()), &jsonMap)
	//fmt.Println(jsonMap)
	return jsonMap
}

func parse_html(n *html.Node, response *PaymentCreateResponse) {
	idFound := false
	if n.Type == html.ElementNode && n.Data == "meta" {
		for _, a := range n.Attr {
			if a.Key == "content" {
				//fmt.Println(a.Val)
				st := a.Val
				if strings.Contains(st, "url") == true {
					words := strings.Split(strings.Split(strings.Split(st, ";")[1], "=")[1], "/")
					res := words[len(words)-2]
					//fmt.Println(res)
					if len(response.Id) == 0 {
						response.Id = res
					}
					idFound = true
				}
				break
			}
		}

	}

	if !idFound {
		for c := n.FirstChild; c != nil; c = c.NextSibling {
			parse_html(c, response)
		}
	}
}

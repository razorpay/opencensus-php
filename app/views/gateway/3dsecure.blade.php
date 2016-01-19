<HTML>
<HEAD>
    <TITLE>Send PARes to TermUrl</TITLE>
</HEAD>
<BODY>
<FORM NAME="postPAResToMPIForm" ACTION="{{{$data['TermUrl']}}}" METHOD="post">
<input type="hidden" name="csrf" value="null">
<TABLE BORDER="1" CELLPADDING="10" CELLSPACING="0" ALIGN="center">
<TR>
<TD ALIGN="center">
<TABLE BORDER="0" CELLPADDING="5" CELLSPACING="0">
    <TR>
        <TH COLSPAN="2" ALIGN="center">
            <FONT SIZE="+2">
                Send PARes to TermUrl
            </FONT>
        </TH>
    </TR>

    <TR>
        <TH ALIGN="center" COLSPAN="2">
            <FONT COLOR="red">
                Click Submit to send this message to {{{$data['TermUrl']}}}
            </FONT>
        </TH>
    </TR>

    <TR>
        <TH ALIGN="right" VALIGN="top">
            Response to PAReq:
        </TH>
        <TD>
            <TEXTAREA ROWS="16" COLS="100" WRAP="on" READONLY>
            &amp;lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&amp;gt;&lt;ThreeDSecure&gt;&lt;Message id=&quot;YAZXo7hHirG7k/b6lLLJZvWWPOM=&quot;&gt;&lt;PARes id=&quot;174204386&quot;&gt;&lt;version&gt;1.0.2&lt;/version&gt;&lt;Merchant&gt;&lt;acqBIN&gt;433274&lt;/acqBIN&gt;&lt;merID&gt;fss -90004415&lt;/merID&gt;&lt;/Merchant&gt;&lt;Purchase&gt;&lt;xid&gt;4xwgBRC14EtBEr6gCIE3d6NadDk=&lt;/xid&gt;&lt;date&gt;20140924 01:44:07&lt;/date&gt;&lt;purchAmount&gt;500&lt;/purchAmount&gt;&lt;currency&gt;356&lt;/currency&gt;&lt;exponent&gt;2&lt;/exponent&gt;&lt;/Purchase&gt;&lt;pan&gt;0000000000001112&lt;/pan&gt;&lt;TX&gt;&lt;time&gt;20140924 12:54:41&lt;/time&gt;&lt;status&gt;Y&lt;/status&gt;&lt;cavv&gt;AAACBwZihRRDIVg2FWKFAAAAAAA=&lt;/cavv&gt;&lt;eci&gt;05&lt;/eci&gt;&lt;cavvAlgorithm&gt;2&lt;/cavvAlgorithm&gt;&lt;/TX&gt;&lt;/PARes&gt;&lt;Signature &gt;&lt;SignedInfo xmlns=&quot;http://www.w3.org/2000/09/xmldsig#&quot;&gt;&lt;CanonicalizationMethod Algorithm=&quot;http://www.w3.org/TR/2001/REC-xml-c14n-20010315&quot;&gt;&lt;/CanonicalizationMethod&gt;&lt;SignatureMethod Algorithm=&quot;http://www.w3.org/2000/09/xmldsig#rsa-sha1&quot;&gt;&lt;/SignatureMethod&gt;&lt;Reference URI=&quot;#174204386&quot;&gt;&lt;DigestMethod Algorithm=&quot;http://www.w3.org/2000/09/xmldsig#sha1&quot;&gt;&lt;/DigestMethod&gt;&lt;DigestValue&gt;s2Tu7YvUcJp9ZmCjIU42EvZh+cg=&lt;/DigestValue&gt;&lt;/Reference&gt;&lt;/SignedInfo&gt;&lt;SignatureValue&gt;sPqk4ESvyx97heZm08dg7Tcu1UlRX4tZKa55osodh7K/LQa3ZYRUo+DY5z91QXyNjtzLPC2+NoSXYlLj4EZZ8AUcQBnS2geR6ivTkDSZEUmuM5Ao9MSHbDam+wFY96KZWfBLcSjGOE9vWC/4GgklETxopXasXRwTQp83LsNPt3U=&lt;/SignatureValue&gt;&lt;KeyInfo&gt;&lt;X509Data&gt;&lt;X509Certificate&gt;MIICQjCCAasCCQChMaX8hzfXgTANBgkqhkiG9w0BAQUFADA+MQswCQYDVQQGEwJVUzEQMA4GA1UEChMHQ2FyYWRhczEMMAoGA1UECxMDUElUMQ8wDQYDVQQDEwZwaXQtY2EwHhcNMTQwMzA2MDUwOTIxWhcNMTkwMzA1MDUwOTIxWjCBjDELMAkGA1UEBhMCVVMxETAPBgNVBAgTCENvbG9yYWRvMRgwFgYDVQQHEw9IaWdobGFuZHMgUmFuY2gxDTALBgNVBAoTBFZJU0ExLzAtBgNVBAsTJlZpc2EgSW50ZXJuYXRpb25hbCBTZXJ2aWNlIEFzc29jaWF0aW9uMRAwDgYDVQQDEwd0ZXN0cGl0MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDsy6vHYivf2Qn5aKeKULOM5H+g9OIeHOEEptgcxi4XxH/4s3Owt/iYDPPSxFv5Armz+1+FwGZBi+ThRKcZVw+dsH63uPVOUkHDGn4beP2HaRtryT3X7qnMThjXHgfnVeUqIp18ZRPTiYNLYjv3/qtSd5SQq6QXxcF7agPklC2TSQIDAQABMA0GCSqGSIb3DQEBBQUAA4GBAMHHLzplBPIlQCoNoZ27tDZt8BGWGYabKPyEEGACXP55X1u3N8lE1v7mF7Tm0N5QPg13wyk9YbBoO/m/CTfOYQvHJG9c6/1052+XGtU3JuNlipb4hTFN4D6k6Z9LUmS//QV/t03YxPFB7S3tCuuXGkUI0m0JfklfpUUbOtwwmmht&lt;/X509Certificate&gt;&lt;X509Certificate&gt;MIICnzCCAgigAwIBAgIJANhcG/IeHwt9MA0GCSqGSIb3DQEBBQUAMEAxCzAJBgNVBAYTAlVTMRAwDgYDVQQKEwdDYXJhZGFzMQwwCgYDVQQLEwNQSVQxETAPBgNVBAMTCHBpdC1yb290MB4XDTE0MDMwNjA0NDYzN1oXDTI0MDMwMzA0NDYzN1owPjELMAkGA1UEBhMCVVMxEDAOBgNVBAoTB0NhcmFkYXMxDDAKBgNVBAsTA1BJVDEPMA0GA1UEAxMGcGl0LWNhMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDS8wkuFUF4kaaiaSL+R56Vakz1ulgoYFq/EoXJzLSw0AtaW81eHuChye87XgDGPXuAECobKR1po7jmmv7N1mqolxdLttAo5KIrW9eON6+/+3S4tIkuKrq+6VLTyxS5tm7HtIk3VHgOauYqZAwdCxSFqIuFjsujhs+XXxvwBuo5swIDAQABo4GiMIGfMB0GA1UdDgQWBBSSeO/Apvd/IYPohAgH1IdESNp/KDBwBgNVHSMEaTBngBTZN94fUNQRn4qO7sSjXtpWHdR7iaFEpEIwQDELMAkGA1UEBhMCVVMxEDAOBgNVBAoTB0NhcmFkYXMxDDAKBgNVBAsTA1BJVDERMA8GA1UEAxMIcGl0LXJvb3SCCQD5xUs3mIvpWzAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBACtXB0vtl0+QUUvHGlo8gqCwjjhwDLpa2VRslausKGt84WlPiX0TH2Bqxm/zmPyBjNnuXWGHmQ4KgFmqa0SeF1AfP/Y3AWeEJA6Joej58nG0hr6CcObxrC+wAMRPDIlLHO+51QyjpNF9HC+k26bxUapZs2VW/2pcP67mtQHyXiYQ&lt;/X509Certificate&gt;&lt;X509Certificate&gt;MIICoTCCAgqgAwIBAgIJAPnFSzeYi+lbMA0GCSqGSIb3DQEBBQUAMEAxCzAJBgNVBAYTAlVTMRAwDgYDVQQKEwdDYXJhZGFzMQwwCgYDVQQLEwNQSVQxETAPBgNVBAMTCHBpdC1yb290MB4XDTE0MDMwNjA0MzUxMFoXDTI0MDMwMzA0MzUxMFowQDELMAkGA1UEBhMCVVMxEDAOBgNVBAoTB0NhcmFkYXMxDDAKBgNVBAsTA1BJVDERMA8GA1UEAxMIcGl0LXJvb3QwgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAKG7diarDQg17UjmvJasHFSjWhPdb9/9pXZvWAKuc9wqqjD3nvU6w+uJtYIFqN4vXC+jk7ek4VF7jvkDF3R00fnHl6wOVufzQlFA7+QXpWTMGsb6yywhXMwVbcO8u14cGV/x+5VewkTgrVRbqZlOXImellNvW1fsJ5HiSVfH8eylAgMBAAGjgaIwgZ8wHQYDVR0OBBYEFNk33h9Q1BGfio7uxKNe2lYd1HuJMHAGA1UdIwRpMGeAFNk33h9Q1BGfio7uxKNe2lYd1HuJoUSkQjBAMQswCQYDVQQGEwJVUzEQMA4GA1UEChMHQ2FyYWRhczEMMAoGA1UECxMDUElUMREwDwYDVQQDEwhwaXQtcm9vdIIJAPnFSzeYi+lbMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADgYEAIeZZtXQqlBK04a2gimGko/aL2YWMRgh04yTK+jw7OkJ/UWdA1g78UJk5/rTJ92579io5rsmLHXV+uWc6Wr6IFO4AfxiQv+GW/PMQ8pu49o8ev9yTvYaos8XP4zdUO4RsXBw9rYRuSP4Ov2tOKKPomOJLabS58GAlCouk774/xTE=&lt;/X509Certificate&gt;&lt;/X509Data&gt;&lt;/KeyInfo&gt;&lt;/Signature&gt;&lt;/Message&gt;&lt;/ThreeDSecure&gt;</TEXTAREA>
        </TD>
    </TR>

    <TR>
        <TD ALIGN="center" COLSPAN="2">
            <INPUT TYPE="submit" value="Submit">
            <INPUT TYPE="hidden" NAME="PaRes" VALUE="eJzFV2mToloS/SsV9T4a3ewiHZQvLjsqyCrLN0RkF5RVf/1Drequ19Mz0TMTE8OXglOZefPcPHnNS/85lsVLH12atDq9vSJf4deX6BRWh/QUv73alvBl8frnkraSSxRxZhR2l2hJK1HTBHH0kh7eXj3guxWZSOlFJHNoPy82m5XfO462Vd5el7QGjKh5GCIkjsI4tphP6Pt6y2m5rygNfXxOgS9hEpzaJR2EZ0ZWlziGoSROQ++fdBldZG55bJqXT88XCoZhHEcIGnr+n4Z+BNK6+1szZT2mhyU+DjFjsAjOtwx/mceszGOHuRocuPyNhu4W9CFooyUKIzhMofgLjHzD8W8wSUMPnK7v4UBZdVNsAoZp6DNAT9tzmXbvusSIOQ19/6Kjsa5O0WQxkf3+TkM/cquD0xL+9CAIMpneUdpyl3Sblp9yQtBvBP4NR2jogdNNG7Rds/Ro6P2NDoO+XwIAWGbw08QwOHkXo4KzFsDzmbg+TOgoTJfwtG/3vw8vUMTVJW2T8p7q3wEauqcCPSq6pM00Pk2LXaKXSUCn5u01adv6GwQNw/B1wL5WlxhCJyIQTEGTwaFJ4z9en17RQT4dq3/LjQ1O1SkNgyK9Be2kFCVqk+rw8j23X4WxjHskBDJ49ssU6kuI4KcvdwTGEGKKCf066Cdmv7PKz8lemuBLkwTIfYGfAi1pIzpGd0VEL7Yhv73+8bkluDSOmvY/WfJjuc8RPuLtgqKLlg1qdaTX2+GqpvySzWQbR/neT2Zh/Pbh97Skoe85vhN4VuvTrryH1M45zpv9daTIJPJLeHGISSvsELswXLz11wFBVE11SMg1tNEDzPcMu5pxHnGjEN29qll722gsOlMr0/WKTYbzvr8AdqgzJxONI2Oe9lbOmT5vl51CgIpSTGnPBeVsEDxqvvadI7MJzUzc8lTvsBAuxnnBW2NVu0HjGoOl1wts06hai9lvnyrxznIdXZ+sXAKmuKANnm9sdGnT4ySJqdMVWWb1jGVB0LCsziZK4C6S29GNLaAycX5O8lSkBpgBui0ADswUvRlY3eN2ui7yw2pn33hdAbgIEJufvCUdFa6eYyThjVcUUD3xUeFsvrAVfTFwT1+OH/whcPXWQ/lBSkJVsfRBuQF0shy2ljw6Dyy/Y8h3LGOZjOM3CsgfcZlEYXc7ZeQtoDGxumNAbLG82u9F6p5DrxjxIMSP9SR+oOTAOVR7Ueh8SYntUug8NB45C2yevpXFCP7KhvlxcwPtE2usVeHXIcrHpkPAvrvqPNeo9yiR7FnGmr7RwFELmRduIUplgSPAgUN1igEGLv7geZj8VDgUC1iRxaMCYJE1z6Ip7zFO5+/7CqbtUwHHMqm+ZmKda67zXvLS/ojqJyJYR2t7s1UIaRZTWzmStjxft3E4prg7ShDeYNuhhVKP0zRzFHoCXMrbDJkJg+gz6cxKjHXo74bZoZHmWKfttnYuceIJ30caKgVGe7lamEueT4qVZK4UH0+7yD7LNbLwDc1KPXXjZT0GnVvzQJj6ea67YyiQQazlBYtapi5zQAfMz5yYJycGKJK0udUFo8mFzlZq5aNky/ntghEd0Qv2a+3K8yJgXY0gXKTD1EXBIz1ZCqRVwiqhazGCDdec8vZMtYVKiLWOW0/vpZVIhXMIgQl05oqtja06tUjrPZ5Ygopz83zuUxu7NCFI30EtjHmjJjCkibVs17libstwCa+OeXGsbXu/bYehLJOWhn7ujF+2yuk2tUqcxmCQJ7XJK6AmoQhNZRla6lfboPBgZG9g9ZSTZ4FiZ32Sx3qSB+e5q8QXhZuiDwP7xDf8oOrmTv8kbcViJaY+sMh1j1KwwuAuZ/GwwimDmgFY5bybilQTJj+wqW0+sEHLftEyHNh+lz08kSiF3HOVkePA+kP6AGFWO47X7rzuvmBUxLuMN46a/KaUzcWQd4It4HkQpIG5mRnEfBfkN6SbTn5POEN85a5uG3OAQRs4CySSOja5RgvSjTlRczvAs9V+bSB1RWZl2ZMqUp6rYjxs2hZUxFq+OFS0VeczaIaZeCvn3fpyns13G+s6mkRbktKEYTsp3gadd/bBcGBHUzjLnZA1XZY0M9cd+4HpKqIZnlKucDF9cGMenA9crDsMY5rRFgJ1f4BkT6sSEEuIfOBNtYbWHDPc90syFT6wmFM8nQoqhR9tVTdO+HlLNmbmtrUjHQwyDQS+5uVB/9UR9vv1MBSw+KiH/KiHu+r3mDkd3xwx2g1Wyn3t3IDyyMtQeMaaDm5dgv5pm7Kty8B9W8Az3bZ7SSyqRXxmhyxLBm5TB+jOaIqga9Ziu8CdQktd2JJQ5jyW0K3UrkymnjrXEaVSx9exUJ4D2IwEBBw1yMOAE/ErMF9VUUYsTiKcXOZsuN2PF3Y2AMXQOLnYSNsZgejXrFYFSmJnOTrfj3ZQ+w26cyC0DrU5Wba6dHVTT//NNq2se5uef7SpdhLMW+Sls2L//2xT5WaPivD3Nn3H/key0IfYf/zqrtaVLyd9qIIHZzBRg8Ek9dX0I82AtUge0uDC6TFC2lnZr4JGEszMSbTDnoKo2p3uOmDdhdRwPmccdurt+TDrVq0nC2cV7112luVklOM7gcz6nBMwA4aPJ6mYD9tdd7zphQDIme7WjqWIzX5+vQ6Jqwy7fbhddAgeijtonBG7aMit+LIz9me/2LpyGRWF2jvIsVkRUmrujtIiuhYgVhgAxCwO5Ac36T5RGPCWYTxeUHMMSygdYcRjWpHduFYjtPAOiNStFAk8WloejFoRI/CvbCvbzPVsquZ/M+0Y/MANH1NA8ph2wpLqD/LPehweefGDLihT14LjP9RLeNaLB3Lk+62rnwtmDeMBGqelmFdQsEE9Zxp2Ehi/WutZNpDbfAXZzgEgMbmwVzkBXawVhRIklVbEpSk3krubdU44dy5zWdji4Dimej8THUibZrS6w6lqEfXU1eq9oGoWrobfDvYWNxqXGaiLZ3Smhm97tN2u11pVblebYG8Sk/oKtupyksSh0eLfftGq0I85FPo+m/6YWh8X2seV+34J+3wV/wtacRym">
            <INPUT TYPE="hidden" NAME="MD" VALUE="{{$data['MD']}}">
            <INPUT TYPE="hidden" NAME="extraUselessField" VALUE="{{$data['MD']}}">
        </TD>
    </TR>

</TABLE>
</TD>
</TR>
</TABLE>
</FORM>
</BODY>
</HTML>

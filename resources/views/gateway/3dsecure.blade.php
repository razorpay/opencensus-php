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

            <?php
            foreach ($data as $key => $value)
            {
                ?>
                <INPUT TYPE="hidden" NAME="{{$key}}" VALUE="{{$value}}">
                <?php
            }
            ?>
        </TD>
    </TR>

</TABLE>
</TD>
</TR>
</TABLE>
</FORM>
</BODY>
</HTML>

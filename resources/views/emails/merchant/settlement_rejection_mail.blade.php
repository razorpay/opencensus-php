<HTML>
<div>
    Hello,
</div>
<br />
<div>
    We regret to inform you that, unfortunately, we would not be able to support your business as the bank has
    not approved your account for activation.
</div>
<br />
<div>
    We request you to kindly look for any other alternative and wish you all the best. Kindly contact support and
    provide proof of delivery to process the settlements.
</div>
<div>
    @if ($further_query_data['type'] === 'website')
        For any further queries or clarifications, feel free to reach out to us by visiting-
        <a rel="noopener noreferrer" href={{ $further_query_data['website_link'] }} target="_blank">{{ $further_query_data['website_link'] }}</a>
    @else
        For any further queries or clarifications, feel free to reach to us by mailing to -
        <a class="link" href="mailto:{{ $further_query_data['email'] }}" style="text-decoration: none; color: #528FF0;">{{ $further_query_data['email'] }}</a>
    @endif
</div>

</HTML>

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>
## The Project

This is a Laravel 11 API for connecting to the MailChimpAPI and extract the data to PowerBI with a Query. The main function of this API works with the next parameters:   
    'data_center' (required|string) => us1 (the datacenter from MailChimp, you need to check your code but it's "us" plus a number).
    'api_key' (required|string) => The API Key that you generate in Mailchimp
    'count' (required|string) => The number of records to return. Default value is 10. Maximum value is 1000, but here you need to pass a specific number.
    'endpoint' (required|string) => "/reports" for example, you choose the endpoint from mailchimp that you want to retrieve.

Here is an example of a Query that works in PowerBI calling the function from the API.

let
    url = "http://localhost:8000/api/getRecords",
    headers = [
        Authorization = "Bearer FREETOKEN",
        #"Content-Type" = "application/x-www-form-urlencoded"
    ],
    body = "action=getRecords" & "&endpoint=/reports" & "&data_center=YourDataCenter" & "&api_key=yourAPIKey" & "&count=100",
    response = Web.Contents(url,
        [
            Headers = headers,
            Content = Text.ToBinary(body)
        ]
    ),
    Source = Json.Document(response), //After this line are some transformations for the visualization of the data, but the query to retrieve the data ends here.
    #"Converted to Table" = Table.FromList(Source, Splitter.SplitByNothing(), null, null, ExtraValues.Error),
    #"Expanded Column1" = Table.ExpandRecordColumn(#"Converted to Table", "Column1", {"id", "campaign_title", "type", "list_id", "list_is_active", "list_name", "subject_line", "preview_text", "emails_sent", "abuse_reports", "unsubscribed", "send_time", "bounces", "forwards", "opens", "clicks", "facebook_likes", "industry_stats", "list_stats", "timeseries", "ecommerce", "delivery_status", "_links"}, {"id", "campaign_title", "type", "list_id", "list_is_active", "list_name", "subject_line", "preview_text", "emails_sent", "abuse_reports", "unsubscribed", "send_time", "bounces", "forwards", "opens", "clicks", "facebook_likes", "industry_stats", "list_stats", "timeseries", "ecommerce", "delivery_status", "_links"}),
    #"Expanded bounces" = Table.ExpandRecordColumn(#"Expanded Column1", "bounces", {"hard_bounces", "soft_bounces", "syntax_errors"}, {"hard_bounces", "soft_bounces", "syntax_errors"}),
    #"Expanded opens" = Table.ExpandRecordColumn(#"Expanded bounces", "opens", {"opens_total", "unique_opens", "open_rate", "last_open"}, {"opens_total", "unique_opens", "open_rate", "last_open"}),
    #"Expanded clicks" = Table.ExpandRecordColumn(#"Expanded opens", "clicks", {"clicks_total", "unique_clicks", "unique_subscriber_clicks", "click_rate", "last_click"}, {"clicks_total", "unique_clicks", "unique_subscriber_clicks", "click_rate", "last_click"}),
    #"Added Custom" = Table.AddColumn(#"Expanded clicks", "url", each "https://us9.admin.mailchimp.com/reports/summary?id=" & [id]),
    #"Changed Type" = Table.TransformColumnTypes(#"Added Custom",{{"id", type text}, {"campaign_title", type text}, {"type", type text}, {"list_id", type text}, {"list_name", type text}, {"subject_line", type text}, {"preview_text", type text}, {"emails_sent", Int64.Type}, {"abuse_reports", Int64.Type}, {"unsubscribed", Int64.Type}, {"send_time", type datetime}, {"hard_bounces", Int64.Type}, {"soft_bounces", Int64.Type}, {"syntax_errors", Int64.Type}, {"opens_total", Int64.Type}, {"unique_opens", Int64.Type}, {"open_rate", type number}, {"last_open", type datetime}, {"clicks_total", Int64.Type}, {"unique_clicks", Int64.Type}, {"unique_subscriber_clicks", Int64.Type}, {"click_rate", type number}, {"last_click", type datetime}, {"url", type text}}),
    #"Filas filtradas" = Table.SelectRows(#"Changed Type", each [send_time] > #datetime(2020, 1, 1, 0, 0, 0))
in
    #"Filas filtradas"
    
## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

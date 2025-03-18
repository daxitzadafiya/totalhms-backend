<DOCTYPE html>
    <html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <h3>Hello, {{$data['name']}}</h3>
        <p><span style="text-transform: capitalize">{{$data['objectType']}}</span> <b>{{$data['objectName']}}</b> {{$data['reason']}}</p>
        <p>View detail: {{$data['url']}}</p>
        <p>HSE team</p>
    </body>
    </html>
</DOCTYPE>

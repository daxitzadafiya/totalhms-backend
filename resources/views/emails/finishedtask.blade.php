<DOCTYPE html>
    <html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>
    <h3>
        <p>Hello,{{$data['name']}}</p>
        <p>Assignee: {{$data['assignee']}}</p>
        <p>Deadline: {{$data['deadline']}}</p>
        <p>Thank you for signing up for HSE. We’re happy you’re here!</p>
        <p>You can start your free trial on the HSE system immediately by activating your account by clicking on this link  <a href="{{$data['url']}}">Join HSE system</a></p>
    </h3>
    </body>
    </html>
</DOCTYPE>

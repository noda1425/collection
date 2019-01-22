<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Index Page</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            
        </style>
    </head>
    <body>
        <div class="content">
            <div>
                <div class="title m-b-md">
                    Json形式のデータをArrayに変換表示します
                </div>
            </div>
        </div>
        <div>
            {!! Form::label('入力') !!}
            {!! Form::textarea('inputData') !!}
        </div>
    </body>
</html>

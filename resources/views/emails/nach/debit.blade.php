<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Please find debit & summary files list below : </title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            border: 1px solid black;
        }

        th, td {
            text-align: left;
            padding: 8px;
            border: 1px solid black;
        }

        tr:nth-child(even){background-color: #f2f2f2}

        th {
            background-color: #04AA6D;
            color: white;
        }
    </style>
</head>
<body>
<p>Hi Team,</p>
<p>&emsp; &emsp; Please find below list of all debit & summary files.</p><br/>
<p>Total number of debit files sent: {{count($debit_files)}}</p>
<p>Total number of summary files sent: {{count($summary_files)}}</p><br/>

<b>debit files list</b>
<br/><br/>

<table>
    <thead>
    <tr>
        <th>S.No</th>
        <th>Name of the debit file</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($debit_files as $file)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $file }}</td>
        </tr
    @endforeach
    </tbody>
</table>

<br/><br/>
<b> summary files list</b>
<br/><br/>

<table>
    <thead>
    <tr>
        <th>S.No</th>
        <th>Name of the summary file</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($summary_files as $file)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $file }}</td>
        </tr
    @endforeach
    </tbody>
</table>

</body>
</html>

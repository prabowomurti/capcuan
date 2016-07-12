<!DOCTYPE html> 
<html>
<head>
	<title>Administrator - Edit Option {option_id}</title>
</head>
<body>
	{manage_option_anchor}<br />
	{message}
	{form_open}
	
	{form_hidden_option_id}
	{form_hidden_option_name}
	
	<table>
		<tr>
			<td>Option Name</td>
			<td>{option_name}</td>
		</tr>
		<tr>
			<td>Option Value</td>
			<td>{form_input_option_value}</td>
		</tr>
		<tr>
			<td></td>
			<td>{form_submit}</td>
		</tr>
	</table>
	
	{form_close}
</body>
</html>

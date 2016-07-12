<!DOCTYPE html> 
<html>
<head>
	<title>Administrator - Add New Blog</title>
</head>
<body>
	{manage_blog_anchor}<br />
	{message}
	{form_open}
	
	<table>
		<tr>
			<td>Blog Owner</td>
			<td>{form_input_blog_owner}</td>
		</tr>
		<tr>
			<td>Blog Title</td>
			<td>{form_input_blog_title}</td>
		</tr>
		<tr>
			<td>Blog URL</td>
			<td>{form_input_blog_url}</td>
		</tr>
		<tr>
			<td>Blog RSS</td>
			<td>{form_input_blog_rss}</td>
		</tr>
		<tr>
			<td></td>
			<td>{form_submit}</td>
		</tr>
	</table>
	
	{form_close}
</body>
</html>

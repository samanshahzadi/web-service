<form action="search.php">
<input type="search" required name="keywords" >
<input type="number" min='1' name="price_min" >
<input type="number" min='1' name="price_max" >
<select name="sorting" >
<option value="default">Default</option>
<option value="by_price_asc">Low to highest prices</option>
</select>
<input type="submit">
</form>
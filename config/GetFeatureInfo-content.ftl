<#list features as feature>
	<!-- (<em>${feature.fid}</em>) -->

	<table style="width:100%">
	<#list feature.attributes as attribute>
		<#if !attribute.isGeometry>
			<tr>
				<th>${attribute.name}</th>
				<td>${attribute.value}</td>
			</tr>
		</#if>
	</#list>
	</table>
</#list>

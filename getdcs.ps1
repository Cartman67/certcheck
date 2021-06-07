#returns all the domain controllers of the current forest in a list ready to copy past in certcheck.php

foreach($domain in (Get-AdForest).Domains)
	{
    foreach($dc in (get-addomaincontroller -filter * -server $domain).hostname)
        {
        """ldaps://$dc"","
        }
	}

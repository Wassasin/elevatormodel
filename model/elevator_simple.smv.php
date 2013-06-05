<?php
	error_reporting(E_ALL);
	if(count($argv) == 2)
		$num_floors = (int)$argv[1];
	else
		$num_floors = 4;
?>
MODULE elevator
	VAR
		cabin_at : 0..<?= $num_floors-1 ?>;
		target : -1..<?= $num_floors-1 ?>;
	ASSIGN
		init(cabin_at) := 0;
		init(target) := -1;
	DEFINE
		has_target := target != -1;
		met_target := cabin_at = target;
		may_change_target := met_target | !has_target;
<?php
for($i = 0; $i < $num_floors; ++$i)
	echo("	FAIRNESS\n		target = $i\n");
?>
		
MODULE floor(i)
	VAR
		door_open : boolean;
		requested : boolean;
	ASSIGN
		init(door_open) := FALSE;
		init(requested) := FALSE;
	DEFINE
		floor := i;

MODULE controller
	VAR
		e : elevator;
<?php
for($i = 0; $i < $num_floors; ++$i)
echo("		f$i : floor($i);\n");
?>
	ASSIGN

<?
for($i = 0; $i < $num_floors; ++$i)
echo("
		next(f$i.door_open) := case
			f$i.floor = e.target & e.met_target : TRUE;
			TRUE : FALSE;
		esac;
");
?>

<?
for($i = 0; $i < $num_floors; ++$i)
echo("
		next(f$i.requested) := case
			FALSE : {TRUE, FALSE};
			f$i.door_open: FALSE;
			TRUE : TRUE;
		esac;
");
?>
		
		next(e.cabin_at) := case
			may_move & e.has_target & e.target < e.cabin_at : e.cabin_at - 1;
			may_move & e.has_target & e.target > e.cabin_at : e.cabin_at + 1;
			TRUE : e.cabin_at;
		esac;
		
		next(e.target) := case
<?php
for($i = 0; $i < $num_floors; ++$i)
echo("			e.may_change_target & f$i.requested : $i;\n");
?>
			e.may_change_target & no_requests & e.cabin_at != 0 : 0;
			e.may_change_target & no_requests & e.cabin_at = 0 : -1;
			TRUE : e.target;
		esac;

	DEFINE
		may_move := (
<?php
for($i = 0; $i < $num_floors; ++$i)
{
	echo("			(f$i.floor = e.cabin_at -> !f$i.door_open)");
	
	if($i == $num_floors-1)
		echo("\n");
	else
		echo(" &\n");
}
?>
		);
		
		no_requests := (
<?php
for($i = 0; $i < $num_floors; ++$i)
{
	echo("			!f$i.requested");
	
	if($i == $num_floors-1)
		echo("\n");
	else
		echo(" &\n");
}
?>
		);
	
MODULE main
	VAR
		c : controller;
		-- The doors are safe
<?php
for($i = 0; $i < $num_floors; ++$i)
	echo("		LTLSPEC G !(c.f$i.door_open & c.e.cabin_at != $i)\n");
?>
		-- A requested floor will be served sometime.
<?php
for($i = 0; $i < $num_floors; ++$i)
	echo("		LTLSPEC G (c.f$i.requested -> F (c.e.cabin_at = $i & c.f$i.door_open))\n");
?>
		-- Again and again the elevator returns to floor 0.
		LTLSPEC G F c.e.cabin_at = 0
		-- Law of nature.
<?php
for($i = 0; $i < $num_floors; ++$i)
{
	echo("		LTLSPEC G c.e.cabin_at = $i -> X (");
	for($j = $i-1; $j <= $i+1; ++$j)
		if($j >= 0 && $j < $num_floors)
		{
			echo("c.e.cabin_at = $j");
			
			if($j < $i+1 && $j < $num_floors-1)
				echo(" | ");
		}
	echo(")\n");
}
?>
		-- The doors will eventually close
<?php
for($i = 0; $i < $num_floors; ++$i)
	echo("		LTLSPEC G c.f$i.door_open -> F !c.f$i.door_open\n");
?>
		-- The target only leaves -1 if there has been done a request in the previous state.
		LTLSPEC G c.e.target != -1 -> Y (c.e.target != -1<?php for($i = 0; $i < $num_floors; ++$i) echo " | c.f$i.requested"; ?>)

		-- The doors have been safe up to now.
<?php
for($i = 0; $i < $num_floors; ++$i)
	echo("		LTLSPEC H !(c.f$i.door_open & c.e.cabin_at != $i)\n");
?>

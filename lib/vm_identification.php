<?php

function get_vm_id()
{

  switch (gethostname())
  {
	case 'vm300.jn-hebergement.com':
	case 'vm377.jn-hebergement.com':
		return '0';
	case 'vm301.jn-hebergement.com':
	case 'vm378.jn-hebergement.com':
		return '1';
	case 'vm302.jn-hebergement.com':
	case 'vm379.jn-hebergement.com':
		return '2';
	case 'vm303.jn-hebergement.com':
		return '3';
	case 'vm412.jn-hebergement.com':
		return '4';
	case 'vm413.jn-hebergement.com':
		return '5';
	case 'vm414.jn-hebergement.com':
		return '6';
	case 'vm415.jn-hebergement.com':
		return '7';
	
	case 'vm310.jn-hebergement.com':
		return '8';
	case 'vm376.jn-hebergement.com':
		return '9';
	
	default:
		return '';
  }

  return '';
}

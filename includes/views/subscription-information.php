<div class="gform_wrapper gforms-stripe-self-serve">

	<div id="gf_form_focus" class="stripe-self-serve-focus" tabindex="-1" ></div>

	<?php if ( count( $errors ) ) { ?>

		<div class="gform_validation_errors gforms-stripe-self-serve-errors" id="gforms_stripe_self_serve_subscriptions_errors_container">

			<h2 class="gform_submission_error">

				<span class="gform-icon gform-icon--close"></span>

				<?php esc_html_e( 'The following errors occurred.', 'gravityformsstripe' ); ?>

			</h2>

			<ol>

				<?php foreach ( $errors as $error ) { ?>
					<li>

						<a class="gform_validation_error_link" href="#gforms_subscription_link_<?php echo $error['subscription']['entry_id']; ?>">
							<?php echo esc_html( $error['subscription']['title'] ) . ': ' . esc_html( $error['message'] ); ?>
						</a>

					</li>
				<?php } ?>

			</ol>

		</div>

		<script type="text/javascript">
			jQuery( document ).ready( function () {
				// Announce validation errors.
				if ( ! jQuery( '.gforms-stripe-self-serve .gform_validation_errors' ).length ) {
					return;
				}

				jQuery( '.stripe-self-serve-focus' ).focus();
				setTimeout( function () {
					var message = jQuery( '.gforms-stripe-self-serve .gform_validation_errors > h2' ).text() + ': ' + jQuery( '.gforms-stripe-self-serve .gform_validation_errors > ol' ).text();
					wp.a11y.speak( message, 'assertive' );
				}, 1000 );
			} );

		</script>

	<?php } ?>

	<div class="gforms-stripe-self-serve-subscriptions-information-container">
		<?php

		if ( ! $subscriptions ) {

			echo esc_html( $no_subscriptions_found_message );

		} else {

			?>
			<ul class="gforms-stripe-self-subscriptions-list">
			<?php

			$rendered_subscriptions_counter = 0;

			foreach ( $subscriptions as $entry_id => $subscription ) {

				$nonce = wp_create_nonce( 'gforms_stripe_self_serve_link_' . $subscription['customer_id'] );

				?>
				<li class="gforms-stripe-subscription" id="gforms_subscription_entry_<?php echo $entry_id; ?>">

					<h2 class="gforms-stripe-subscription-title" >

						<?php echo esc_html( $subscription['plan_title'] ); ?>

					</h2>

					<div class="gforms-stripe-subscription-details">

						<div class="gforms-stripe-subscription-status">
							<?php echo esc_html__( 'Status', 'gravityformsstripe' ) . ': ' . ucfirst( rgar( $subscription, 'status' ) ); ?>
						</div>

						<div class="gforms-stripe-subscription-start">
							<?php echo esc_html__( 'Start Date', 'gravityformsstripe' ) . ': ' . date( 'M, jS Y', rgar( $subscription, 'start_date' ) ); ?>
						</div>

						<div class="gforms-stripe-subscription-billing-cycle">

							<span class="gforms-stripe-subscription-billing-cycle-money">
								<?php echo GFCommon::to_money( rgar( $subscription, 'price' ), rgar( $subscription, 'currency' ) ); ?>
							</span>

							<span class="gforms-stripe-subscription-billing-cycle-amount">
								<?php echo esc_html__( 'per', 'gravityformsstripe' ) . ' ' . rgar( $subscription, 'frequency' ); ?>
							</span>

						</div>

					</div>

					<div class="gforms-stripe-subscription-portal-link">

						<form method="post" id="gforms_subscription_<?php echo $entry_id; ?>">

							<input type="hidden" name="gforms_stripe_self_serve_link_nonce" value="<?php echo $nonce; ?>" />
							<input type="hidden" name="gforms_stripe_customer_id" value="<?php echo $subscription['customer_id']; ?>" />
							<input type="hidden" name="gforms_stripe_entry_id" value="<?php echo $entry_id; ?>" />
							<input type="hidden" name="gforms_stripe_subscription_title" value="<?php echo $subscription['plan_title']; ?>" />

							<button type="submit" id="gforms_subscription_link_<?php echo $entry_id; ?>">
								<?php esc_html_e( 'Manage subscription', 'gravityformsstripe' ); ?>
							</button>

						</form>

					</div>
				<?php
				$rendered_subscriptions_counter++;
				if ( $rendered_subscriptions_counter < count( $subscriptions ) ) {
					echo '<hr class="gforms-stripe-subscription-details-separator" >';
				}
				?>

				</li>
				<?php
			}
		}
		?>

		</ul>

		<script type="text/javascript">
			document.getElementById('gf_form_focus').focus();
		</script>

	</div>

</div>

<style>
	ul.gforms-stripe-self-subscriptions-list li {
		list-style-type: none;
		margin:0;
	}

	.gforms-stripe-subscription-portal-link button {
		margin-top: 1.25rem;
	}
</style>

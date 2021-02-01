# M2BitcoinPayment
Bitcoin direct payment module for Magento2.

We use the term "direct" as opposed modules that actually integrate 3rd pary payment gateway. This solution requires you to run your own bitcoin client at a network-accessible location and enable RPC communication.

# Motivation
Although there are a very few Magento2 modules that integrate bitcoin payments without the use of a 3rd party, they all are for older Magento versions and, lack documentation and, most importantly, they lack a slick self-reloading payment page where the customer can scan a QR code with his wallet for paying. These already existent modules are incompatible with the latest bitcoin-core module.


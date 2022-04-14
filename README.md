# Ikarus SPS Global GPIO System Management

During runtime of an Ikarus SPS there are several needs of accessing pins, either on Raspberry Pi or any extension module.  
A common problem can be, that a gpio extender is accessed via i2c. This means that pins are organized by blocks, normally 8 pins are represented by one byte. Trying to change one bit, the others are overwritten.

This package ships with a global gpio driving manager that coordinates those accessed.

### Installation
```bin
$ composer require ikarus/sps-gpio-driver
```

#### Any default Ikarus SPS already includes this package.
CREATE TABLE currency_rate (
  currency varchar(4) NOT NULL,
  day date NOT NULL,
  rate decimal(12,7) NOT NULL,
  PRIMARY KEY (currency,day)
);

# Digitell Live Events Platform API Documentation - Session Entry

When a remote server needs to securely authenticate a user to enter a
session hosted on the Digitell Live Events Platform they do so by using a
private key certificate to sign a JWT (JSON Web Token) containing
the user's registration details required to build their identity, 
and the session to enter.

### Identity
An identity represents a single user on the Digitell Live Events Platform, it
is mainly comprised of a unique identifier, a name, and an email address, plus
other information necessary for handling the back-end.

When you authenticate a user to the Live Events Platform, you only need to be
concerned about passing the identifier, name and email. Although there are methods
available to handle existing identities, it is recommended that you use the complete
registration method, which will create a new identity if it does not exist, or 
update the information of a previously existing one (if necessary).

#### Identifier (string) [2-80 characters]
The identifier looks very much like a domain login, it is comprised of two parts.
The first is the client identifier, this will usually be a short alphanumeric string
that matches the name of your certificate. The second is a unique identifier that
is unique to your integration.

> ClientID\YourUniqueIdentifier

The unique portion can be anything you want, so long as it conforms to basic 
alphanumerics (no whitespace). While it is possible to use an email address, doing
so is strongly discouraged as if the email address changes, the user will generate
a second identity on the Live Events Platform.

> example\12345

> example\joe@blogs.co.uk

It is highly recommended that you use some form of database identifier as your
unique identifier, such as the primary key of the row representing the user in your
main databases.

The unique id chosen MUST NOT allow collisions. For example, if you run a yearly event
and each year has a new database, and such the potential for database keys to overlap
year-on-year, you MUST take steps to properly namespace them, such as adding the year as 
a prefix:

> example\\2019_12345

It is extremely important that any sites performing integrations take care to 
ensure that only one person can access a particular identity, and two different 
people should never share the same unique id

#### Name (string) [2-35 characters]
The display name of the user. This will be visible to other attendees within the session,
as well as included in diagnostics and reporting. Multiple users can share the same display
name within a session.

Limitations: Supports most UTF8 although certain pages may be filtered.

#### Email (string) [5-70 characters]
The email address of the user. This is mainly used to provide for the delivery of follow-up
emails, as well as providing additional technical support if necessary. A user's email address
is NOT visible to most attendees although it is visible to authorized hosts and Digitell, Inc. 
staff. 

Digitell Live Events Platform expects that organizations using integrations will take care
to ensure that email addresses that are used are legitimate and have been verified. The authentication
system may reject email addresses it believes to be invalid or undeliverable, such as if they 
contain an invalid hostname.

---

### Session (string) [5 - 60 characters]
A session is a unit of broadcast. Almost everything on the Digitell Live Events Plaform is broken
up into individual sessions, and it is through those sessions that authentication and reporting is
handled.

For authentication, the only thing you need to provide is the session reference code, which will typically
be your client id, followed by a random string.

---

## Using the Authentication API
An API helper has been provided in the Digitell\LiveEventsPlatformAPI\Sessions\BrowserAuth\IdentitySessionEntryBuilder
class. 

```php
$client_id = 'example';
$unique_id = '12345';

$builder = new IdentitySessionEntryBuilder(
    $client_id,
    file_get_contents(__DIR__ . '/' . $client_id . '.pem')
);

$builder
    ->setName('Joe Blogs')
    ->setEmail('joe.blogs@exmaple.com')
    ->setIdentifier($client_id . '\\' . $unique_id)
    ->setSessionReference('abc-123456')
    ->toUrl();

echo $builder->toUrl();
```

In the above example, the certificate has been sourced from a PEM file located in the
same directory as the example script. It is required that you store your private key (PEM)
file somewhere secure, such as in your database or in a restricted access file outside of
your webroot.

If you have reason to believe your private key may have been compromised, please contact your
Digitell, Inc. representative immediately to have it reset.

## More
For more information contact James Newman (jnewman@digitellinc.com)
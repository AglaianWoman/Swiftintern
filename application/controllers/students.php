<?php

/**
 * Description of users
 *
 * @author Faizan Ayubi
 */
use Shared\Controller as Controller;
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;

class Students extends Controller {

    /**
     * @before _secure
     */
    public function index() {
        $this->changeLayout();
        $profile = 0;

        $this->seo(array(
            "title" => "Profile",
            "keywords" => "user profile",
            "description" => "Your Profile Page",
            "view" => $this->getLayoutView()
        ));

        $view = $this->getActionView();

        $session = Registry::get("session");
        $user = $this->user;
        $student = $session->get("student");

        $qualifications = Qualification::all(
                        array(
                    "student_id = ?" => $student->id
                        ), array("id", "degree", "major", "organization_id", "gpa", "passing_year")
        );

        $works = Work::all(
                        array(
                    "student_id = ?" => $student->id
                        ), array("id", "designation", "responsibility", "organization_id", "duration")
        );

        $socials = Social::all(
                        array(
                    "user_id = ?" => $user->id
                        ), array("id", "social_platform", "link")
        );

        $resumes = Resume::all(
                        array(
                    "student_id = ?" => $student->id
                        ), array("id", "type")
        );

        if (count($qualifications))
            ++$profile;
        if (count($works))
            ++$profile;
        if (!empty($student->about))
            ++$profile;
        if (!empty($student->skills))
            ++$profile;


        $view->set("student", $student);
        $view->set("qualifications", $qualifications);
        $view->set("works", $works);
        $view->set("profile", $profile * 100 / 4);
        $view->set("resumes", $resumes);
        $view->set("socials", $socials);
    }

    /**
     * Does three important things, first is retrieving the posted form data, second is checking each form field’s value
     * third thing it does is to create a new user row in the database
     */
    public function register() {
        $li = Framework\Registry::get("linkedin");

        $this->seo(array(
            "title" => "Get Internship | Student Register",
            "keywords" => "get internship, student register",
            "description" => "Register with us to get internship from top companies in india and various startups in Delhi, Mumbai, Bangalore, Chennai, hyderabad etc",
            "view" => $this->getLayoutView()
        ));

        $url = $li->getLoginUrl(array(
            LinkedIn::SCOPE_FULL_PROFILE,
            LinkedIn::SCOPE_EMAIL_ADDRESS,
            LinkedIn::SCOPE_CONTACT_INFO
        ));

        $view = $this->getActionView();
        $view->set("url", $url);

        if (isset($_REQUEST['code'])) {
            $token = $li->getAccessToken($_REQUEST['code']);
            $token_expires = $li->getAccessTokenExpiration();
        }

        if ($li->hasAccessToken()) {
            $info = $li->get('/people/~:(first-name,last-name,positions,email-address,public-profile-url,location,picture-url,educations,skills)');
            if ($info["phoneNumbers"]["_total"] > 0) {
                $phone = $info["phoneNumbers"]["values"]["0"]["phoneNumber"];
            } else {
                $phone = "";
            }
            $user = new User(array(
                "name" => $info["firstName"] . " " . $info["lastName"],
                "email" => $info["emailAddress"],
                "phone" => $phone,
                "password" => rand(100000, 99999999),
                "access_token" => rand(100000, 99999999),
                "type" => "student",
                "validity" => "1"
            ));

            if ($user->save()) {
                
                $social = new Social(array(
                    "user_id" => $user->id,
                    "social_platform" => "linkedin",
                    "link" => $info["publicProfileUrl"]
                ));
                $social->save();
                
                if (isset($info["location"]["name"])) {
                    $city = $info["location"]["name"];
                } else {
                    $city = "";
                }
                $skills = "";
                if ($info["skills"]["_total"] > 0) {
                    foreach ($info["skills"]["values"] as $key => $value) {
                        $skills .= $value["skill"]["name"];
                        $skills .= ",";
                    }
                }

                $student = new Student(array(
                    "user_id" => $user->id,
                    "city" => $city,
                    "skills" => $skills
                ));

                if ($student->save()) {
                    /**
                     * Saving Education Info
                     */
                    if ($info["educations"]["_total"] > 0) {
                        foreach ($info["educations"]["values"] as $key => $value) {
                            $org = Organization::first(array("name = ?" => $value["schoolName"]),array("id"));
                            if($org){
                                $orgId = $org->id;
                            }else{
                                $organization = new Organization(array(
                                    "name" => $value["schoolName"]
                                ));
                                if($organization->save()){
                                    $orgId = $organization->id;
                                }
                            }
                            $qualification = new Qualification(array(
                                "student_id" => $student->id,
                                "organization_id" => $orgId,
                                "degree" => $value["degree"],
                                "major" => $value["fieldOfStudy"],
                                "gpa" => "",
                                "passing_year" => $value["endDate"]["year"]
                            ));
                            $qualification->save();
                        }
                    }
                    
                    /**
                     * Adding work experience
                     */
                    if ($info["positions"]["_total"] > 0) {
                        foreach ($info["positions"]["values"] as $key => $value) {
                            $org = Organization::first(array("name = ?" => $value["company"]["name"]),array("id"));
                            if($org){
                                $orgId = $org->id;
                            }else{
                                $organization = new Organization(array(
                                    "name" => $value["company"]["name"]
                                ));
                                if($organization->save()){
                                    $orgId = $organization->id;
                                }
                            }
                            $work = new Work(array(
                                "student_id" => $student->id,
                                "organization_id" => $orgId,
                                "duration" => "from ".$value["startDate"]["year"],
                                "designation" => $value["title"],
                                "responsibility" => $value["summary"]
                            ));
                            $work->save();
                        }
                    }
                    
                }
            }
        }
    }

    /**
     * @before _secure
     */
    public function profile($id) {
        $profile = 0;
        $view = $this->getActionView();

        $user = User::first(
                        array(
                    "id = ?" => $id
                        ), array("id", "name", "email", "type")
        );

        $student = Student::first(
                        array("user_id = ?" => $user->id)
        );

        $this->seo(array(
            "title" => "Profile",
            "keywords" => "user profile",
            "description" => "Your Profile Page",
            "view" => $this->getLayoutView()
        ));

        $qualifications = Qualification::all(
                        array(
                    "student_id = ?" => $student->id
                        ), array("id", "degree", "major", "organization_id", "gpa", "passing_year")
        );

        $works = Work::all(
                        array(
                    "student_id = ?" => $student->id
                        ), array("id", "designation", "responsibility", "organization_id", "duration")
        );

        $socials = Social::all(
                        array(
                    "user_id = ?" => $user->id
                        ), array("id", "social_platform", "link")
        );

        $resumes = Resume::all(
                        array(
                    "student_id = ?" => $student->id
                        ), array("id", "type")
        );

        if (count($qualifications))
            ++$profile;
        if (count($works))
            ++$profile;
        if (!empty($student->about))
            ++$profile;
        if (!empty($student->skills))
            ++$profile;


        $view->set("student", $student);
        $view->set("user", $user);
        $view->set("qualifications", $qualifications);
        $view->set("works", $works);
        $view->set("profile", $profile * 100 / 4);
        $view->set("resumes", $resumes);
        $view->set("socials", $socials);
    }

    /**
     * @before _secure
     */
    public function messages() {
        $this->changeLayout();
        $seo = Registry::get("seo");

        $seo->setTitle("Messages");
        $seo->setKeywords("user messages");
        $seo->setDescription("Your Inbox/Outbox");

        $this->getLayoutView()->set("seo", $seo);
        $view = $this->getActionView();

        $user = $this->user;

        $inboxs = Message::all(
                        array(
                    "to_user_id = ?" => $user->id,
                    "validity = ?" => true
                        ), array("id", "from_user_id", "message", "created")
        );

        $outboxs = Message::all(
                        array(
                    "from_user_id = ?" => $user->id,
                    "validity = ?" => true
                        ), array("id", "to_user_id", "message", "created")
        );

        $view->set("inboxs", $inboxs);
        $view->set("outboxs", $outboxs);
    }

    /**
     * @before _secure
     */
    public function applications() {
        $this->defaultLayout = "layouts/student";
        $this->setLayout();
        $seo = Registry::get("seo");

        $seo->setTitle("Applications");
        $seo->setKeywords("student opportunity applications");
        $seo->setDescription("Your Application and its status");

        $this->getLayoutView()->set("seo", $seo);
        $view = $this->getActionView();

        $session = Registry::get("session");
        $student = $session->get("student");

        $applications = Application::all(
                        array(
                    "student_id = ?" => $student->id
                        ), array("id", "opportunity_id", "status", "created", "updated")
        );

        $view->set("applications", $applications);
    }

    /**
     * @before _secure
     */
    public function settings() {
        $this->defaultLayout = "layouts/student";
        $this->setLayout();
        $errors = array();
        $seo = Registry::get("seo");

        $seo->setTitle("Settings");
        $seo->setKeywords("student opportunity applications");
        $seo->setDescription("Update your profile");

        $this->getLayoutView()->set("seo", $seo);
        $view = $this->getActionView();

        $session = Registry::get("session");
        $student = $session->get("student");
        $user = $this->getUser();

        if (RequestMethods::post("action") == "settings") {
            $user = new User(array(
                "name" => RequestMethods::post("name", $user->name),
                "email" => RequestMethods::post("email", $user->email),
                "phone" => RequestMethods::post("phone", $user->phone)
            ));

            if ($user->validate()) {
                $user->save();
                $this->user = $user;
                $view->set("success", true);
            }
        }

        $view->set("student", $student);
        $view->set("errors", $user->errors);
    }

    /**
     * @before _secure
     */
    public function qualification() {
        $this->defaultLayout = "layouts/student";
        $this->setLayout();
        $seo = Registry::get("seo");

        $seo->setTitle("Add Education Details");
        $seo->setKeywords("");
        $seo->setDescription("Use this form to add education details");

        $this->getLayoutView()->set("seo", $seo);
        $view = $this->getActionView();

        $session = Registry::get("session");
        $student = $session->get("student");
    }

    /**
     * @before _secure
     */
    public function work() {
        $this->defaultLayout = "layouts/student";
        $this->setLayout();
        $seo = Registry::get("seo");

        $seo->setTitle("Applications");
        $seo->setKeywords("student opportunity applications");
        $seo->setDescription("Your Application and its status");

        $this->getLayoutView()->set("seo", $seo);
        $view = $this->getActionView();

        $session = Registry::get("session");
        $student = $session->get("student");
    }

    /**
     * @before _secure
     */
    public function recommended() {
        $this->defaultLayout = "layouts/student";
        $this->setLayout();
        $seo = Registry::get("seo");

        $seo->setTitle("Matching Opportunity with your profile");
        $seo->setKeywords("opportunity matching with you profile");
        $seo->setDescription("opportunity matching with you profile");

        $this->getLayoutView()->set("seo", $seo);
        $view = $this->getActionView();

        $session = Registry::get("session");
        $student = $session->get("student");

        $opportunities = Opportunity::all(
                        array(
                    "title LIKE ?" => "%{$student->skills}%",
                    "eligibility LIKE ?" => "%{$student->skills}%"
                        ), array("id", "title", "organization_id", "eligibility", "last_date", "location")
        );

        $view->set("opportunities", $opportunities);
        $view->set("student", $student);
    }

    public function changeLayout() {
        $this->defaultLayout = "layouts/student";
        $this->setLayout();
    }

}

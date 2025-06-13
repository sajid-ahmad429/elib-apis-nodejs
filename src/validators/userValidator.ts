import { body } from "express-validator";

const registerUserValidator = [
  body("firstName")
    .notEmpty().withMessage("First name is required"),

  body("lastName")
    .notEmpty().withMessage("Last name is required"),

  body("userName")
    .notEmpty().withMessage("Username is required")
    .isLowercase().withMessage("Username must be in lowercase"),

  body("email")
    .notEmpty().withMessage("Email is required")
    .isEmail().withMessage("Valid email is required"),

  body("password")
    .notEmpty().withMessage("Password is required")
    .isLength({ min: 8 }).withMessage("Password must be at least 8 characters"),

  body("role")
    .notEmpty().withMessage("Role is required")
    .isIn(["user", "admin", "superadmin"]).withMessage("Role must be user, admin, or superadmin"),

  body("status")
    .notEmpty().withMessage("Status is required")
    .isIn(["active", "inactive", "suspended"]).withMessage("Status must be active, inactive, or suspended"),
];


const loginUserValidator = [
  body("email")
    .notEmpty().withMessage("Email is required")
    .isEmail().withMessage("Please enter a valid email"),

  body("password")
    .notEmpty().withMessage("Password is required"),
];

export { registerUserValidator, loginUserValidator };
